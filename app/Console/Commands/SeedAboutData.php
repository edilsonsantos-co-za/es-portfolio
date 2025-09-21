<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Contracts\AboutInterface;

class SeedAboutData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:about';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the about table with demo data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $about = resolve(AboutInterface::class);

            $data = [
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
                'avatar' => 'assets/common/img/avatar/default.png',
                'cover' => 'assets/common/img/cover/default.png',
                'phone' => '12025550191',
                'address' => '1609 Nuzum Court, Cheektowaga, NY 14225',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Non corporis assumenda maiores. Impedit quia necessitatibus adipisci sit quibusdam aspernatur mollitia, deleniti, id, molestiae a accusantium modi sint expedita aliquam labore.',
                'taglines' => ["I am Software Engineer", "I am Web Developer", "I am Full Stack Engineer"],
                'social_links' => [
                    [
                        'title' => 'LinkedIn',
                        'iconClass' => 'fab fa-linkedin-in',
                        'link' => 'https://www.linkedin.com'
                    ],
                    [
                        'title' => 'Github',
                        'iconClass' => 'fab fa-github',
                        'link' => 'https://github.com'
                    ],
                    [
                        'title' => 'Twitter',
                        'iconClass' => 'fab fa-twitter',
                        'link' => 'https://twitter.com'
                    ],
                    [
                        'title' => 'Mail',
                        'iconClass' => 'far fa-envelope',
                        'link' => 'mailto:johndoe@example.com'
                    ],
                ],
                'seederCV' => 'assets/common/cv/default.pdf',
            ];

            $result = $about->store($data);

            if ($result['status'] === 200) {
                $this->info('✅ About data seeded successfully!');
                $this->info('Name: ' . ($data['name'] ?? 'Unknown'));
                $this->info('Email: ' . ($data['email'] ?? 'Unknown'));
                
                // Verify by checking if data exists
                $verification = $about->getAll();
                if ($verification['status'] === 200) {
                    $this->info('✅ Verification: About data is now present in database');
                } else {
                    $this->warn('⚠️  Warning: About data may not have been saved properly');
                }
                
                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to seed about data:');
                $this->error($result['message'] ?? 'Unknown error');
                if (isset($result['payload'])) {
                    $this->error('Details: ' . json_encode($result['payload']));
                }
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error seeding about data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}