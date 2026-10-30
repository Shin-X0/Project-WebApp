<?php

namespace App\Command;

use App\Entity\Artwork;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Seeds the same demo data the old db/art_terre.sql imported:
 *   demo@artterre.com / password123  (artist — also an admin via ADMIN_EMAILS)
 *   admin@artterre.com / admin12345  (admin)
 * plus a couple of demo artworks so the pages are not empty.
 */
#[AsCommand(name: 'app:seed', description: 'Seed demo users and artworks (same as the old db/art_terre.sql)')]
class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /* ---------- Demo artist (also admin through ADMIN_EMAILS) ---------- */
        $demo = $this->em->getRepository(User::class)->findOneBy(['email' => 'demo@artterre.com']);
        if (!$demo) {
            $demo = new User();
            $demo->setName('Demo Artist');
            $demo->setEmail('demo@artterre.com');
            $demo->setRole(User::ROLE_ARTIST);
            $demo->setDob(new \DateTimeImmutable('1990-06-15'));
            $demo->setGender('male');
            $demo->setPassword($this->hasher->hashPassword($demo, 'password123'));
            $this->em->persist($demo);
            $io->writeln('Created demo artist: demo@artterre.com / password123');
        } else {
            $demo->setPassword($this->hasher->hashPassword($demo, 'password123'));
        }

        /* ---------- Site admin ---------- */
        $admin = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@artterre.com']);
        if (!$admin) {
            $admin = new User();
            $admin->setName('Site Admin');
            $admin->setEmail('admin@artterre.com');
            $admin->setRole(User::ROLE_ADMIN);
            $admin->setDob(new \DateTimeImmutable('1990-01-01'));
            $admin->setGender('prefer-not');
            $admin->setPassword($this->hasher->hashPassword($admin, 'admin12345'));
            $this->em->persist($admin);
            $io->writeln('Created admin: admin@artterre.com / admin12345');
        } else {
            $admin->setPassword($this->hasher->hashPassword($admin, 'admin12345'));
        }

        /* ---------- Demo artworks (so the catalogue is not empty) ---------- */
        $titles = [
            'Morning Dusk'        => ['painting', 'Uploaded from the demo artist account — replace it with your own work.', '3.50'],
            'Golden Hour Fields'  => ['painting', 'A warm landscape study painted on a late afternoon walk.', '4.20'],
            'Quiet Corridor'      => ['photography', 'A still architectural moment — light and silence in balance.', '2.99'],
        ];

        $repo = $this->em->getRepository(Artwork::class);
        foreach ($titles as $title => [$category, $story, $price]) {
            if (!$repo->findOneBy(['title' => $title])) {
                $artwork = new Artwork();
                $artwork->setArtist($demo);
                $artwork->setTitle($title);
                $artwork->setCategory($category);
                $artwork->setStory($story);
                $artwork->setPrice($price);
                $this->em->persist($artwork);
                $io->writeln("Seeded artwork: {$title}");
            }
        }

        $this->em->flush();
        $io->success('Seed complete.');

        return Command::SUCCESS;
    }
}
