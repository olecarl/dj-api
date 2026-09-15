<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\MusicAlbum;
use App\Entity\MusicGroup;
use App\Entity\MusicPlaylist;
use App\Entity\MusicRecording;
use App\Entity\User;
use App\Tests\Support\FunctionalTester;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MusicResourceCest
{
    public function testPlaylistContainsOrderedSchemaMusicResources(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'music@example.com');
        $data = $this->createMusicData($I);
        $I->amLoggedInAs($user, 'api');
        $token = $I->grabService(JWTTokenManagerInterface::class)->create($user);
        $I->haveHttpHeader('Authorization', 'Bearer '.$token);

        $I->sendGet('/music/'.$data['playlist']->getId());

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertSame((string) $data['playlist']->getId(), $response['id']);
        $I->assertSame('Setlist', $response['name']);
        $I->assertSame(2, $response['numTracks']);
        $I->assertSame('https://schema.org/ItemListOrderAscending', $response['track']['itemListOrder']);
        $I->assertSame(1, $response['track']['itemListElement'][0]['position']);
        $I->assertSame('First Song', $response['track']['itemListElement'][0]['item']['name']);
        $I->assertSame(2, $response['track']['itemListElement'][1]['position']);
        $I->assertSame('Second Song', $response['track']['itemListElement'][1]['item']['name']);
        $I->assertStringEndsWith('/music/albums/'.(string) $data['album']->getId(), $response['track']['itemListElement'][0]['item']['inAlbum']);
        $I->assertStringEndsWith('/music/artists/'.(string) $data['group']->getId(), $response['track']['itemListElement'][0]['item']['byArtist'][0]);
    }

    public function testMusicCollectionsAndRelatedResourcesAreAvailable(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'music@example.com');
        $data = $this->createMusicData($I);
        $I->amLoggedInAs($user, 'api');
        $token = $I->grabService(JWTTokenManagerInterface::class)->create($user);

        foreach ([
            '/music' => 'Setlist',
            '/music/recordings' => 'First Song',
            '/music/albums' => 'Album One',
            '/music/artists' => 'The Example Group',
        ] as $path => $name) {
            $I->haveHttpHeader('Authorization', 'Bearer '.$token);
            $I->sendGet($path);

            $I->seeResponseCodeIs(200);
            $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
            $I->assertSame($name, $response['member'][0]['name']);
        }

        $I->haveHttpHeader('Authorization', 'Bearer '.$token);
        $I->sendGet('/music/recordings/'.$data['firstRecording']->getId());
        $I->seeResponseCodeIs(200);
        $recording = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        $I->assertSame('PT3M42S', $recording['duration']);
        $I->assertStringEndsWith('/music/albums/'.(string) $data['album']->getId(), $recording['inAlbum']);
    }

    public function testAnonymousUserCannotReadMusic(FunctionalTester $I): void
    {
        $I->sendGet('/music');

        $I->seeResponseCodeIs(401);
    }

    public function testMissingMusicResourceIsNotFound(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'music@example.com');
        $I->amLoggedInAs($user, 'api');
        $token = $I->grabService(JWTTokenManagerInterface::class)->create($user);
        $I->haveHttpHeader('Authorization', 'Bearer '.$token);

        foreach (['/music', '/music/recordings', '/music/albums', '/music/artists'] as $path) {
            $I->sendGet($path.'/00000000-0000-4000-8000-000000000000');

            $I->seeResponseCodeIs(404);
        }
    }

    public function testOpenApiDocumentsAllMusicResources(FunctionalTester $I): void
    {
        $user = $this->createUser($I, 'music@example.com');
        $I->amLoggedInAs($user, 'api');
        $I->sendGet('/docs.jsonopenapi');

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true, 512, \JSON_THROW_ON_ERROR);
        foreach ([
            '/music',
            '/music/{id}',
            '/music/recordings',
            '/music/recordings/{id}',
            '/music/albums',
            '/music/albums/{id}',
            '/music/artists',
            '/music/artists/{id}',
        ] as $path) {
            $I->assertArrayHasKey($path, $response['paths']);
        }

        foreach (['MusicPlaylist', 'MusicRecording', 'MusicAlbum', 'MusicGroup'] as $schema) {
            $I->assertArrayHasKey($schema, $response['components']['schemas']);
        }
    }

    /**
     * @return array{playlist: MusicPlaylist, firstRecording: MusicRecording, album: MusicAlbum, group: MusicGroup}
     */
    private function createMusicData(FunctionalTester $I): array
    {
        $group = (new MusicGroup())->setName('The Example Group')->setUrl('https://example.com/group');
        $album = (new MusicAlbum())->setName('Album One')->setUrl('https://example.com/album')->addArtist($group);
        $firstRecording = (new MusicRecording())
            ->setName('First Song')
            ->setDuration('PT3M42S')
            ->setUrl('https://example.com/first-song')
            ->setAlbum($album)
            ->setAlbumPosition(1)
            ->addArtist($group);
        $secondRecording = (new MusicRecording())
            ->setName('Second Song')
            ->setDuration('PT4M10S')
            ->setAlbum($album)
            ->setAlbumPosition(2)
            ->addArtist($group);
        $playlist = (new MusicPlaylist())->setName('Setlist');
        $playlist->addTrack($secondRecording, 2)->addTrack($firstRecording, 1);

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($group);
        $entityManager->persist($album);
        $entityManager->persist($firstRecording);
        $entityManager->persist($secondRecording);
        $entityManager->persist($playlist);
        $entityManager->flush();

        return [
            'playlist' => $playlist,
            'firstRecording' => $firstRecording,
            'album' => $album,
            'group' => $group,
        ];
    }

    private function createUser(FunctionalTester $I, string $email): User
    {
        $user = (new User())->setEmail($email);
        $user->setPassword($I->grabService(UserPasswordHasherInterface::class)->hashPassword($user, 'correct horse battery staple'));

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
