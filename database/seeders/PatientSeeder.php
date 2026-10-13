<?php

namespace Database\Seeders;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('patients')->exists()) {
            $this->command?->warn('Patients already exist; skipping PatientSeeder to avoid duplicates.');

            return;
        }

        $lastNames = [
            'Casiño', 'Emano', 'Cabunoc', 'Achas', 'Neri', 'Flores', 'Asiñero', 'Naelga',
            'Quilang', 'Salcedo', 'Nabong', 'Lucagbo', 'Sabio', 'Dagus', 'Tadeo', 'Ando',
            'Pagtalunan', 'Emata', 'Fabela', 'Barros', 'Quinquiño', 'Cabanes', 'Valdehuesa', 'Ejem',
            'Fernandez', 'Rivera', 'Gadian', 'Sanchez', 'Janio', 'del Rosario',
            'Ragandang', 'Pagara', 'Lucaban', 'Bagay', 'dela Cruz', 'Abrogueña', 'Ramos', 'Garcia',
            'Tagolimot', 'Raiz', 'Jagualing', 'Aquiman', 'Burias', 'Lee', 'Nacasabog', 'Bade',
            'Tapulgo', 'Ubal-Ubal', 'Razalo', 'General', 'Labadan', 'Ybañez', 'Rodriguez', 'Pepito',
            'Salva', 'Mahinay', 'Sabejon', 'Roa', 'Seno', 'Escabarte',
            'Martinez', 'Eduave', 'Nacawili', 'Gonzales', 'Villanueva', 'Gaid', 'Abao', 'Reyes',
            'Tan', 'Yañez', 'Go', 'Lim', 'Navarro', 'Torres', 'Lopez', 'Perez',
            'Castillo', 'Santos', 'Mendoza', 'Bautista', 'De Guzman', 'Cruz', 'Domingo', 'Santiago',
            'Soriano', 'Delos Santos', 'Diaz', 'Hernandez', 'Tolentino', 'Valdez', 'Ramirez', 'Morales',
            'Mercado', 'Aguilar', 'Manalo', 'Gomez', 'Dizon', 'Javier', 'Corpuz', 'Gutierrez',
        ];

        $firstNamesMale = [
            'Bryce', 'Kirk', 'Arjohn', 'Paolo', 'Jonas', 'Paolo', 'Marco', 'Ryan',
            'Bryan', 'James', 'Lorenzo', 'John Mark', 'Joshue James', 'Rafael', 'Emmanuel',
            'Victor', 'Francis', 'Jerome', 'Mat', 'Marlon', 'Joe Neil', 'Raidon',
            'Eduardo', 'Ferdinand', 'John Robert', 'Carlo', 'Carl', 'Xander', 'Luis', 'Adrian',
        ];

        $firstNamesFemale = [
            'Marian', 'Aaliyah', 'Bella', 'Camella', 'Dinah', 'Ella', 'Faith Marie', 'Gemma',
            'Hanna Lea', 'Iza', 'Lou', 'Jonah', 'Kristine', 'Lolita', 'Melissa',
            'Nadia', 'Perenz', 'Queen Heart', 'Cristina', 'Andrea', 'Camille', 'Jasmine',
            'Nicole', 'Stephanie', 'Sheena', 'Reychel', 'Angelika', 'Shiellah', 'Leila', 'Raquelle',
        ];

        $patients = [];
        $zoneNumbers = DB::table('zones')->pluck('zone_number', 'id')->toArray();

        // 45 households - 100 patients (50 male, 50 female), some patients share a household.
        $households = [];
        $householdCount = 45;
        for ($i = 0; $i < $householdCount; $i++) {
            $zoneId = array_rand($zoneNumbers);
            $lastName = $lastNames[array_rand($lastNames)];
            $householdCreatedAt = now()->subDays(rand(0, 89));
            $householdId = DB::table('households')->insertGetId([
                'zone_id' => $zoneId,
                'head_first_name' => $firstNamesMale[array_rand($firstNamesMale)],
                'head_middle_name' => $lastNames[array_rand($lastNames)],
                'head_last_name' => $lastName,
                'contact_number' => '09'.rand(100000000, 999999999),
                'created_at' => $householdCreatedAt,
                'updated_at' => $householdCreatedAt,
            ]);
            $households[] = ['id' => $householdId, 'zone_id' => $zoneId, 'last_name' => $lastName];
        }

        $sexes = array_merge(array_fill(0, 50, 'Male'), array_fill(0, 50, 'Female'));
        shuffle($sexes);

        for ($i = 0; $i < 100; $i++) {
            // First 45 patients get their own household, the rest join an existing one.
            $household = $i < $householdCount ? $households[$i] : $households[array_rand($households)];

            $isMale = $sexes[$i] === 'Male';
            $firstName = $isMale ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
            $lastName = $household['last_name'];

            // Age is exactly 23-87: birthday falls between Jan 1 and today of (current year - age).
            $age = rand(23, 87);
            $birthDate = Carbon::createFromDate(now()->year - $age, 1, 1)
                ->addDays(rand(0, (int) now()->startOfYear()->diffInDays(now()->startOfDay())));
            $isChild = $birthDate->diffInYears(now()) < 18;
            $civilStatus = $age >= 25 && rand(0, 1) ? 'Married' : 'Single';
            $isPhilhealthMember = rand(0, 1) === 1;
            $isPcbMember = rand(0, 1) === 1;
            $zoneNumber = $zoneNumbers[$household['zone_id']] ?? $household['zone_id'];

            // Patient created_at spread across the last 90 days to match consultation window
            $patientCreatedAt = now()->subDays(rand(0, 89));

            $patients[] = [
                'household_id' => $household['id'],
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $lastNames[array_rand($lastNames)],
                'suffix' => $isMale && rand(0, 5) === 0 ? 'Jr.' : null,
                'sex' => $isMale ? 'Male' : 'Female',
                'date_of_birth' => $birthDate->format('Y-m-d'),
                'birth_place' => 'Sta. Ana, Tagoloan',
                'blood_type' => ['A+', 'B+', 'O+', 'AB+'][rand(0, 3)],
                'civil_status' => $civilStatus,
                'educational_attainment' => 'High School Graduate',
                'employment_status' => 'Unemployed',
                'mother_name' => $firstNamesFemale[array_rand($firstNamesFemale)].' '.$lastNames[array_rand($lastNames)],
                'spouse_name' => $civilStatus === 'Married'
                    ? ($isMale ? $firstNamesFemale[array_rand($firstNamesFemale)] : $firstNamesMale[array_rand($firstNamesMale)]).' '.$lastNames[array_rand($lastNames)]
                    : '',
                'family_relationship' => Patient::FAMILY_RELATIONSHIP_OPTIONS[array_rand(Patient::FAMILY_RELATIONSHIP_OPTIONS)],
                'residential_address' => $zoneNumber.' Sta. Ana, Tagoloan',
                'is_philhealth_member' => $isPhilhealthMember ? 'y' : 'n',
                'status_type' => $isPhilhealthMember ? Patient::PHILHEALTH_STATUS_TYPES[array_rand(Patient::PHILHEALTH_STATUS_TYPES)] : null,
                'philhealth_no' => $isPhilhealthMember ? sprintf('%02d-%09d-%d', rand(10, 99), rand(100000000, 999999999), rand(0, 9)) : null,
                'membership_category' => $isPhilhealthMember ? Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES[array_rand(Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES)] : null,
                'is_pcb_member' => $isPcbMember ? 'y' : 'n',
                'has_4ps' => rand(0, 1),
                'has_nhts' => rand(0, 1),
                'is_immunization_enrolled' => $isChild,
                'created_at' => $patientCreatedAt,
                'updated_at' => $patientCreatedAt,
            ];
        }

        DB::table('patients')->insert($patients);
    }
}
