<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;
use App\Models\JobAdvert;
use Illuminate\Support\Facades\Storage;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobAdverts = JobAdvert::pluck('id')->all();

        if (empty($jobAdverts)) {
            $this->command->info('No job adverts found. Please run JobAdvertSeeder first.');
            return;
        }

        // Sample CV content templates
        $cvTemplates = [
            "PROFESSIONAL SUMMARY\nHighly motivated and skilled professional with 5+ years of experience in software development. Proficient in multiple programming languages including PHP, JavaScript, and Python. Strong background in web development, database management, and agile methodologies.\n\nSKILLS\n- PHP/Laravel Framework\n- JavaScript/Vue.js/React\n- MySQL/PostgreSQL\n- Git Version Control\n- RESTful API Development\n- Agile/Scrum\n\nEXPERIENCE\nSenior Developer | Tech Solutions Inc.\n- Led development of enterprise web applications\n- Mentored junior developers\n- Implemented CI/CD pipelines\n\nEDUCATION\nBachelor of Computer Science\nUniversity of Technology, 2018",
            "PERSONAL PROFILE\nDedicated and enthusiastic professional seeking to leverage technical expertise and passion for innovation in a dynamic organization. Committed to delivering high-quality solutions and contributing to team success.\n\nTECHNICAL SKILLS\n- Full-stack Development\n- Cloud Computing (AWS/Azure)\n- DevOps Practices\n- Test-Driven Development\n- UI/UX Design Principles\n\nPROFESSIONAL EXPERIENCE\nSoftware Engineer | Innovation Labs\n- Developed scalable web applications\n- Collaborated with cross-functional teams\n- Optimized application performance\n- Implemented security best practices\n\nACADEMIC BACKGROUND\nMaster of Information Technology\nState University, 2019",
            "SUMMARY STATEMENT\nResults-oriented technology professional with a proven track record of delivering innovative solutions. Excel in fast-paced environments and thrive on solving complex technical challenges.\n\nCORE COMPETENCIES\n- System Architecture Design\n- Database Optimization\n- API Integration\n- Project Management\n- Quality Assurance\n- Team Leadership\n\nWORK HISTORY\nTechnical Lead | Digital Solutions Corp\n- Architected microservices infrastructure\n- Led digital transformation initiatives\n- Managed development teams\n- Established coding standards\n\nEDUCATION\nBachelor of Software Engineering\nInstitute of Technology, 2017"
        ];

        // Sample cover letter templates
        $coverLetterTemplates = [
            "Dear Hiring Manager,\n\nI am writing to express my strong interest in the position advertised. With my background in software development and passion for creating innovative solutions, I am confident I would be a valuable addition to your team.\n\nThroughout my career, I have demonstrated expertise in full-stack development, particularly with Laravel and modern JavaScript frameworks. I have successfully delivered multiple projects on time and within budget, while maintaining high code quality standards.\n\nI am particularly drawn to this opportunity because it aligns perfectly with my skills and career goals. I am eager to bring my technical expertise and collaborative approach to contribute to your organization's success.\n\nThank you for considering my application. I look forward to the opportunity to discuss how my background and enthusiasm can benefit your team.\n\nBest regards,",
            "Dear Recruitment Team,\n\nI am excited to apply for this position, as it represents an excellent opportunity to contribute my skills and experience to a forward-thinking organization. My professional journey has equipped me with the technical proficiency and problem-solving abilities necessary to excel in this role.\n\nIn my previous positions, I have successfully managed complex projects, mentored junior developers, and implemented best practices that improved team productivity and code quality. I am proficient in modern development technologies and methodologies, including agile practices and continuous integration.\n\nWhat particularly attracts me to this position is the opportunity to work on innovative projects that make a real difference. I am committed to delivering exceptional results and contributing to the continued success of your organization.\n\nI would welcome the opportunity to discuss how my background and skills align with your needs. Thank you for considering my application.\n\nSincerely,",
            "Dear Hiring Committee,\n\nI am thrilled to submit my application for this exciting opportunity. As a dedicated professional with extensive experience in technology and software development, I am confident in my ability to make significant contributions to your team.\n\nMy career has been marked by a commitment to excellence, innovation, and continuous learning. I have successfully led development initiatives, collaborated with diverse teams, and delivered solutions that exceeded expectations. My technical expertise spans multiple domains, and I am always eager to tackle new challenges.\n\nI am particularly impressed by your organization's reputation for innovation and quality. I am eager to bring my skills, experience, and enthusiasm to contribute to your continued success and growth.\n\nThank you for your time and consideration. I look forward to the possibility of discussing how I can contribute to your organization's objectives.\n\nBest regards,"
        ];

        // Sample applicant data
        $applicantData = [
            ['name' => 'John Smith', 'email' => 'john.smith@email.com', 'phone' => '+254712345678'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@email.com', 'phone' => '+254723456789'],
            ['name' => 'Michael Brown', 'email' => 'michael.brown@email.com', 'phone' => '+254734567890'],
            ['name' => 'Emily Davis', 'email' => 'emily.davis@email.com', 'phone' => '+254745678901'],
            ['name' => 'David Wilson', 'email' => 'david.wilson@email.com', 'phone' => '+254756789012'],
            ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@email.com', 'phone' => '+254767890123'],
            ['name' => 'James Taylor', 'email' => 'james.taylor@email.com', 'phone' => '+254778901234'],
            ['name' => 'Maria Garcia', 'email' => 'maria.garcia@email.com', 'phone' => '+254789012345'],
            ['name' => 'Robert Martinez', 'email' => 'robert.martinez@email.com', 'phone' => '+254790123456'],
            ['name' => 'Jennifer Lopez', 'email' => 'jennifer.lopez@email.com', 'phone' => '+254701234567'],
            ['name' => 'William Rodriguez', 'email' => 'william.rodriguez@email.com', 'phone' => '+254712345679'],
            ['name' => 'Linda Hernandez', 'email' => 'linda.hernandez@email.com', 'phone' => '+254723456780'],
            ['name' => 'Richard Gonzalez', 'email' => 'richard.gonzalez@email.com', 'phone' => '+254734567891'],
            ['name' => 'Patricia Perez', 'email' => 'patricia.perez@email.com', 'phone' => '+254745678902'],
            ['name' => 'Charles Sanchez', 'email' => 'charles.sanchez@email.com', 'phone' => '+254756789013'],
            ['name' => 'Barbara Ramirez', 'email' => 'barbara.ramirez@email.com', 'phone' => '+254767890124'],
            ['name' => 'Joseph Torres', 'email' => 'joseph.torres@email.com', 'phone' => '+254778901235'],
            ['name' => 'Susan Flores', 'email' => 'susan.flores@email.com', 'phone' => '+254789012346'],
            ['name' => 'Thomas Rivera', 'email' => 'thomas.rivera@email.com', 'phone' => '+254790123457'],
            ['name' => 'Margaret Cooper', 'email' => 'margaret.cooper@email.com', 'phone' => '+254701234568'],
        ];

        $statuses = ['Pending', 'Shortlisted', 'Rejected', 'Invited'];
        $statusWeights = [60, 20, 15, 5]; // Mostly pending, some shortlisted, fewer rejected/invited

        // Create applications for random job adverts
        foreach ($jobAdverts as $jobAdvertId) {
            // Randomly decide how many applications per job (0-8 applications per job)
            $numApplications = rand(0, 8);

            if ($numApplications === 0) {
                continue; // Some jobs might have no applications
            }

            // Shuffle applicant data to get random applicants
            $shuffledApplicants = $applicantData;
            shuffle($shuffledApplicants);

            for ($i = 0; $i < $numApplications && $i < count($shuffledApplicants); $i++) {
                $applicant = $shuffledApplicants[$i];
                $cvContent = $cvTemplates[array_rand($cvTemplates)];
                $coverLetter = $coverLetterTemplates[array_rand($coverLetterTemplates)];

                // Randomly select status based on weights
                $status = $this->weightedRandomSelection($statuses, $statusWeights);

                Application::create([
                    'job_advert_id' => $jobAdvertId,
                    'name' => $applicant['name'],
                    'email' => $applicant['email'],
                    'phone' => $applicant['phone'],
                    'cv_blob' => $cvContent, // Store CV content as binary
                    'cover_letter' => $coverLetter,
                    'status' => $status,
                    'submitted_at' => now()->subDays(rand(0, 30)), // Submitted within last 30 days
                ]);
            }
        }

        $this->command->info('Application seeder completed successfully!');
    }

    /**
     * Select a random item from an array based on weights
     */
    private function weightedRandomSelection(array $items, array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $cumulativeWeight = 0;
        foreach ($items as $index => $item) {
            $cumulativeWeight += $weights[$index];
            if ($random <= $cumulativeWeight) {
                return $item;
            }
        }

        return $items[0]; // Fallback
    }
}