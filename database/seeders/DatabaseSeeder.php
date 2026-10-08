<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ROLES
        $roles = [
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Nurse'],
            ['id' => 3, 'role_name' => 'Midwife'],
            ['id' => 4, 'role_name' => 'BHW'],
            ['id' => 5, 'role_name' => 'Doctor'],
        ];
        DB::table('user_roles')->insertOrIgnore($roles);

        // 2. PERMISSIONS + role assignment
        $this->call(PermissionSeeder::class);
        $this->call(AssignInitialRolesSeeder::class);

        // 3. INITIAL USERS (Admin, BHW, Nurse, Doctor)
        // All passwords: password123
        $this->call(CreateInitialUsersSeeder::class);

        // 4. DIAGNOSIS - common PH diseases for local search testing
        // Expanded with additional codes used by the consultation seeder mapping.
        $diagnoses = [
            ['diagnosis_code' => 'J00', 'diagnosis_name' => 'Acute Nasopharyngitis (Common Cold)', 'category' => 'Respiratory'],
            ['diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute Upper Respiratory Infection (URTI)', 'category' => 'Respiratory'],
            ['diagnosis_code' => 'I10', 'diagnosis_name' => 'Essential (Primary) Hypertension', 'category' => 'Circulatory'],
            ['diagnosis_code' => 'E11', 'diagnosis_name' => 'Type 2 Diabetes Mellitus', 'category' => 'Endocrine'],
            ['diagnosis_code' => 'A90', 'diagnosis_name' => 'Dengue Fever', 'category' => 'Infectious'],
            ['diagnosis_code' => 'A09', 'diagnosis_name' => 'Infectious Gastroenteritis (Diarrhea)', 'category' => 'Infectious'],
            ['diagnosis_code' => 'T14.1', 'diagnosis_name' => 'Open Wound', 'category' => 'Injury'],
            ['diagnosis_code' => 'J45.9', 'diagnosis_name' => 'Asthma', 'category' => 'Respiratory'],
            ['diagnosis_code' => 'K52.9', 'diagnosis_name' => 'Noninfective Gastroenteritis and Colitis', 'category' => 'Digestive'],
            ['diagnosis_code' => 'N39.0', 'diagnosis_name' => 'Urinary Tract Infection, Site Not Specified', 'category' => 'Genitourinary'],
            ['diagnosis_code' => 'H10.9', 'diagnosis_name' => 'Conjunctivitis, Unspecified', 'category' => 'Eye'],
            ['diagnosis_code' => 'H66.9', 'diagnosis_name' => 'Otitis Media, Unspecified', 'category' => 'Ear'],
            ['diagnosis_code' => 'K02.9', 'diagnosis_name' => 'Dental Caries, Unspecified', 'category' => 'Digestive'],
            ['diagnosis_code' => 'B86', 'diagnosis_name' => 'Scabies', 'category' => 'Skin'],
            ['diagnosis_code' => 'M54.5', 'diagnosis_name' => 'Low Back Pain', 'category' => 'Musculoskeletal'],
            ['diagnosis_code' => 'R07.4', 'diagnosis_name' => 'Chest Pain', 'category' => 'Circulatory'],
            ['diagnosis_code' => 'F41.9', 'diagnosis_name' => 'Anxiety Disorder, Unspecified', 'category' => 'Mental Health'],
        ];
        DB::table('diagnosis_lookup')->insertOrIgnore($diagnoses);

        // 5. MEDICINES - basic RHU formulary + DOH Essential Medicines
        // Using updateOrCreate keyed on name for idempotency; include form for new EML entries.
        $now = now();
        $medicines = [
            // Existing (keep; fix typo)
            ['name' => 'Paracetamol', 'form' => null],
            ['name' => 'Amoxicillin', 'form' => null],
            ['name' => 'Metformin', 'form' => null],
            ['name' => 'Biogesic', 'form' => null],
            ['name' => 'Losartan', 'form' => null],
            ['name' => 'Amlodipine', 'form' => null],
            ['name' => 'Bioflu', 'form' => null],
            ['name' => 'ORS (Oral Rehydration Salts) Sachet', 'form' => 'Sachet'],
            ['name' => 'Cetirizine', 'form' => null],
            ['name' => 'Salbutamol Inhaler', 'form' => 'Inhaler'],
            ['name' => 'Ibuprofen', 'form' => null],
            ['name' => 'Azithromycin', 'form' => null],
            ['name' => 'Vitamin C', 'form' => null],
            ['name' => 'Erythromycin', 'form' => null],  // fixed from Erethromycin
            ['name' => 'Diatabs', 'form' => null],

            // DOH Essential Medicines additions with strength/form
            ['name' => 'Paracetamol 500mg', 'form' => 'Tablet'],
            ['name' => 'Ibuprofen 400mg', 'form' => 'Tablet'],
            ['name' => 'Amoxicillin 500mg', 'form' => 'Capsule'],
            ['name' => 'Azithromycin 500mg', 'form' => 'Tablet'],
            ['name' => 'Ciprofloxacin 500mg', 'form' => 'Tablet'],
            ['name' => 'Metronidazole 500mg', 'form' => 'Tablet'],
            ['name' => 'Erythromycin 250mg', 'form' => 'Tablet'],
            ['name' => 'Amlodipine 5mg', 'form' => 'Tablet'],
            ['name' => 'Losartan 50mg', 'form' => 'Tablet'],
            ['name' => 'Hydrochlorothiazide 25mg', 'form' => 'Tablet'],
            ['name' => 'Metformin 500mg', 'form' => 'Tablet'],
            ['name' => 'Salbutamol 100mcg/dose', 'form' => 'Inhaler'],
            ['name' => 'Cetirizine 10mg', 'form' => 'Tablet'],
            ['name' => 'Loratadine 10mg', 'form' => 'Tablet'],
            ['name' => 'Zinc Sulfate 20mg', 'form' => 'Tablet'],
            ['name' => 'Loperamide 2mg', 'form' => 'Capsule'],
            ['name' => 'Ferrous Sulfate + Folic Acid 400mcg', 'form' => 'Tablet'],
            ['name' => 'Calcium + Vitamin D3', 'form' => 'Tablet'],
            ['name' => 'Povidone-Iodine 10%', 'form' => 'Solution'],
            ['name' => 'Erythromycin Ointment 0.5%', 'form' => 'Ointment'],
            ['name' => 'Ascorbic Acid 500mg', 'form' => 'Tablet'],
            ['name' => 'Permethrin 5% Cream', 'form' => 'Cream'],
            ['name' => 'Paracetamol 120mg/5mL Syrup', 'form' => 'Syrup'],
        ];

        foreach ($medicines as $med) {
            DB::table('medicines_lookup')->updateOrInsert(
                ['name' => $med['name']],
                ['form' => $med['form'], 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // 6. ZONES
        $zoneIds = [];
        for ($i = 1; $i <= 7; $i++) {
            $zoneIds[] = DB::table('zones')->insertGetId([
                'zone_number' => "$i",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 6a. Assign each zone to a BHW
        $bhwIds = DB::table('health_workers')
            ->where('role', 'BHW')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        foreach ($zoneIds as $i => $zoneId) {
            if (isset($bhwIds[$i])) {
                DB::table('zones')->where('id', $zoneId)->update(['assigned_worker_id' => $bhwIds[$i]]);
            }
        }

        // 7. VACCINES - EPI / immunization lookup
        $this->call(VaccineSeeder::class);

        // 7a. PH LOCATIONS - provinces + cities/municipalities for birth place autocomplete
        $this->call(LocationSeeder::class);

        // 8. ICD-10 diagnosis codes (requires icd102019syst_codes.sql in storage/app/ or BHCIS_ICD_SQL_PATH)
        $this->call(IcdDiagnosisSeeder::class);

        // 9. PATIENTS + households
        $this->call(PatientSeeder::class);

        // 9a. INFANTS with existing immunization records
        $this->call(InfantImmunizationSeeder::class);

        // 10. CONSULTATIONS + outward referrals
        $this->call(ConsultationSeeder::class);

        // 11. Sample postnatal record (with prior prenatal record)
        $this->call(PostnatalRecordSeeder::class);

        // 12. Sample audit logs
        $this->call(AuditLogSeeder::class);
    }
}
