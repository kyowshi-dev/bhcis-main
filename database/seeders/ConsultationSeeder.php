<?php

namespace Database\Seeders;

use App\Enums\ConsultationStatus;
use App\Models\HealthWorker;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Demonstration consultations for the last 3 months: triage vitals,
 * linked ICD diagnoses, and prescriptions drawn from the DOH formulary,
 * with status rows kept coherent with ConsultationService rules
 * (a consultation auto-completes only when it has both a diagnosis
 * and a prescription).
 */
class ConsultationSeeder extends Seeder
{
    /** Complaint numbers that present with fever (drives temperature). */
    private const FEVER_COMPLAINTS = [1, 26, 31, 35];

    /** Complaint numbers that suggest elevated blood pressure in older adults. */
    private const HYPERTENSIVE_COMPLAINTS = [5, 7, 23, 26, 28, 40];

    /** Patient indexes (by id order) whose first consultation is not completed. */
    private const SPECIAL_STATUSES = [
        0 => ConsultationStatus::NurseReview,
        1 => ConsultationStatus::DoctorReview,
        2 => ConsultationStatus::InProgress,
        3 => ConsultationStatus::Referred,
        4 => ConsultationStatus::Referred,
        5 => ConsultationStatus::Referred,
    ];

    /** Chance (%) that a patient with a settled first visit also returns for a follow-up. */
    private const FOLLOW_UP_PERCENT = 30;

    public function run(): void
    {
        if (DB::table('consultations')->exists()) {
            $this->command?->warn('Consultations already exist; skipping ConsultationSeeder to avoid duplicates.');

            return;
        }

        $patients = Patient::query()->orderBy('id')->get();
        $healthWorkers = HealthWorker::all();

        if ($patients->isEmpty() || $healthWorkers->isEmpty()) {
            $this->command?->warn('Please run PatientSeeder and ensure health workers exist before running ConsultationSeeder.');

            return;
        }

        $nurse = $healthWorkers->firstWhere('role', 'Nurse') ?? $healthWorkers->first();
        $doctor = $healthWorkers->firstWhere('role', 'Doctor') ?? $healthWorkers->first();
        $encoder = $healthWorkers->firstWhere('role', 'BHW') ?? $nurse;

        $diagnosisIds = DB::table('diagnosis_lookup')->pluck('id', 'diagnosis_code');
        $medicineIds = DB::table('medicines_lookup')->pluck('id', 'name');

        $counts = [
            'consultations' => 0,
            'vitals' => 0,
            'diagnoses' => 0,
            'prescriptions' => 0,
            'referrals' => 0,
        ];

        // Guarantee one diabetes follow-up so E11 and Metformin always show up
        // in demo reports: complaint 17 only exists for ages 56-78, so a random
        // draw can otherwise miss diabetes entirely on a given seed run.
        $diabetesShowcaseId = $patients
            ->filter(fn (Patient $p, int $i) => ! isset(self::SPECIAL_STATUSES[$i]) && (int) $p->age >= 56 && (int) $p->age <= 78)
            ->first()
            ?->id;

        foreach ($patients as $index => $patient) {
            $status = self::SPECIAL_STATUSES[$index] ?? ConsultationStatus::Completed;
            $patientDaysAgo = $this->daysSince($patient->created_at);
            $firstDaysAgo = rand(0, $patientDaysAgo);
            $firstAt = $this->timestampFor($firstDaysAgo);

            $complaintNumber = $patient->id === $diabetesShowcaseId
                ? 17
                : $this->complaintNumberFor($patient, $index);
            $diagnosisCode = $patient->id === $diabetesShowcaseId
                ? 'E11'
                : $this->diagnosisCodeFor($complaintNumber, $patient);

            $this->insertConsultation(
                $patient,
                $encoder,
                $nurse,
                $doctor,
                $status,
                $complaintNumber,
                $diagnosisCode,
                $firstAt,
                $diagnosisIds,
                $medicineIds,
                $counts,
            );

            // Follow-up only when the first visit was a settled completed visit at
            // least a week ago, so the pair reads as one coherent episode of care.
            if (
                $status === ConsultationStatus::Completed
                && $firstDaysAgo >= 7
                && rand(1, 100) <= self::FOLLOW_UP_PERCENT
            ) {
                $gap = rand(7, min(30, $firstDaysAgo));
                $this->insertConsultation(
                    $patient,
                    $encoder,
                    $nurse,
                    $doctor,
                    ConsultationStatus::Completed,
                    $complaintNumber,
                    $diagnosisCode,
                    $this->timestampFor($firstDaysAgo - $gap),
                    $diagnosisIds,
                    $medicineIds,
                    $counts,
                    isFollowUp: true,
                );
            }
        }

        $this->command?->info(sprintf(
            'Consultations seeded: %d consultations, %d vitals, %d diagnoses, %d prescriptions, %d referrals.',
            $counts['consultations'],
            $counts['vitals'],
            $counts['diagnoses'],
            $counts['prescriptions'],
            $counts['referrals'],
        ));
    }

    /**
     * Insert one consultation plus every clinical row its status calls for.
     *
     * @param  Collection<int, int|string>  $diagnosisIds  code => diagnosis_lookup id
     * @param  Collection<string, int|string>  $medicineIds  name => medicines_lookup id
     * @param  array<string, int>  $counts
     */
    private function insertConsultation(
        Patient $patient,
        HealthWorker $encoder,
        HealthWorker $nurse,
        HealthWorker $doctor,
        ConsultationStatus $status,
        int $complaintNumber,
        string $diagnosisCode,
        Carbon $createdAt,
        $diagnosisIds,
        $medicineIds,
        array &$counts,
        bool $isFollowUp = false,
    ): void {
        $chiefComplaints = $this->chiefComplaints();
        $isTerminal = in_array($status, [ConsultationStatus::Completed, ConsultationStatus::Referred], true);
        $hasNurseValidation = $status !== ConsultationStatus::NurseReview;
        $hasDiagnosis = in_array($status, [
            ConsultationStatus::InProgress,
            ConsultationStatus::Completed,
            ConsultationStatus::Referred,
        ], true);

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id,
            'status' => $status->value,
            'nurse_validated_at' => $hasNurseValidation ? $this->after($createdAt, rand(20, 180)) : null,
            'nurse_validated_by' => $hasNurseValidation ? $nurse->id : null,
            'attending_doctor_id' => $hasDiagnosis ? $doctor->id : null,
            'is_locked' => $isTerminal,
            'complaint_text' => $chiefComplaints[$complaintNumber],
            'nature_of_visit' => $isFollowUp ? 'Follow-up' : (rand(0, 9) < 6 ? 'Initial Consultation' : 'Follow-up'),
            'notes' => $isTerminal
                ? 'Patient evaluated and treated. Advised on medication adherence and return if symptoms persist.'
                : 'Patient presented with symptoms. Advised on home care and follow-up.',
            'mode_of_transaction' => $status === ConsultationStatus::Referred
                ? 'Referral'
                : ['Walk-in', 'Visited', 'Referral'][rand(0, 2)],
            'referred_from' => $status === ConsultationStatus::Referred ? 'Barangay Health Center' : null,
            'worker_id' => in_array($status, [ConsultationStatus::NurseReview, ConsultationStatus::DoctorReview], true)
                ? $nurse->id
                : $encoder->id,
            'created_at' => $createdAt,
            'updated_at' => $this->after($createdAt, rand(180, 360)),
        ]);
        $counts['consultations']++;

        // Every real consultation starts with a triage vitals row.
        DB::table('vitals')->insert($this->vitalsPayload($consultationId, $patient, $complaintNumber, $nurse, $createdAt));
        $counts['vitals']++;

        if (! $hasDiagnosis) {
            // Nurse/doctor review stops before any clinical impression is recorded.
            return;
        }

        $diagnosisId = $diagnosisIds[$diagnosisCode] ?? $diagnosisIds['J06.9'] ?? null;
        $diagnosedAt = $this->after($createdAt, rand(40, 240));

        if ($diagnosisId !== null) {
            DB::table('diagnosis_records')->insert([
                'consultation_id' => $consultationId,
                'diagnosis_id' => $diagnosisId,
                'custom_diagnosis_name' => null,
                'remarks' => $status === ConsultationStatus::Referred
                    ? 'Impression pending confirmation at the referral facility.'
                    : null,
                'diagnosed_by' => $doctor->id,
                'created_at' => $diagnosedAt,
                'updated_at' => $diagnosedAt,
            ]);
            $counts['diagnoses']++;
        }

        // In-progress visits have a diagnosis but no prescription written yet,
        // mirroring maybeAutoComplete() which needs both to flip to completed.
        if ($status === ConsultationStatus::InProgress) {
            return;
        }

        foreach ($this->prescriptionItems($diagnosisCode, (int) $patient->age) as $item) {
            $medicineId = $medicineIds[$item['name']] ?? null;
            $writtenAt = $this->after($diagnosedAt, rand(2, 30));

            DB::table('prescriptions')->insert([
                'consultation_id' => $consultationId,
                'medicine_id' => $medicineId,
                'custom_medicine_name' => $medicineId === null ? $item['name'] : null,
                'quantity' => $item['quantity'],
                'dosage' => $item['dosage'],
                'route' => $item['route'],
                'frequency' => $item['frequency'],
                'duration' => $item['duration'],
                'instructions' => $item['instructions'],
                'created_at' => $writtenAt,
                'updated_at' => $writtenAt,
            ]);
            $counts['prescriptions']++;
        }

        if ($status === ConsultationStatus::Referred) {
            $this->insertReferral($consultationId, $chiefComplaints[$complaintNumber], $diagnosedAt);
            $counts['referrals']++;
        }
    }

    /**
     * @param  array<string, int|string>  $medicineIds
     * @return array<string, int>
     */
    private function vitalsPayload(
        int $consultationId,
        Patient $patient,
        int $complaintNumber,
        HealthWorker $nurse,
        Carbon $createdAt,
    ): array {
        $age = (int) $patient->age;
        $feverish = in_array($complaintNumber, self::FEVER_COMPLAINTS, true);
        $temperature = $feverish ? rand(380, 392) / 10 : rand(364, 375) / 10;

        $systolic = null;
        $diastolic = null;
        $weight = null;
        $height = null;

        if ($age < 2) {
            $weight = rand(35, 110) / 10;
            $height = rand(45, 85);
        } elseif ($age < 18) {
            $weight = rand(14, 60);
            $height = rand(95, 165);
            $systolic = rand(90, 115);
            $diastolic = rand(55, 75);
        } else {
            $weight = rand(45, 95);
            $height = rand(145, 178);

            $hypertensive = $age >= 40 && in_array($complaintNumber, self::HYPERTENSIVE_COMPLAINTS, true);
            if ($hypertensive) {
                $systolic = rand(140, 165);
                $diastolic = rand(90, 100);
            } elseif ($age >= 60) {
                $systolic = rand(115, 145);
                $diastolic = rand(70, 95);
            } elseif ($age >= 40) {
                $systolic = rand(110, 135);
                $diastolic = rand(70, 88);
            } else {
                $systolic = rand(100, 125);
                $diastolic = rand(65, 82);
            }
        }

        $capturedAt = $this->after($createdAt, rand(5, 25));

        return [
            'consultation_id' => $consultationId,
            'phase' => 'triage',
            'captured_by' => $nurse->id,
            'bp_systolic' => $systolic === null ? null : (string) $systolic,
            'bp_diastolic' => $diastolic === null ? null : (string) $diastolic,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'temperature_c' => $temperature,
            'bmi' => null,
            'notes' => $feverish ? 'Patient reports fever with chills.' : null,
            'created_at' => $capturedAt,
            'updated_at' => $capturedAt,
        ];
    }

    private function insertReferral(int $consultationId, string $complaintText, Carbon $createdAt): void
    {
        $referredAt = $this->after($createdAt, rand(5, 30));

        DB::table('outward_referrals')->insert([
            'consultation_id' => $consultationId,
            'destination_facility' => 'Tagoloan District Hospital',
            'pertinent_history' => $complaintText,
            'actions_taken' => 'Initial assessment and supportive care given.',
            'specific_details' => 'Referred for specialist consultation.',
            'status' => 'pending',
            'created_at' => $referredAt,
            'updated_at' => $referredAt,
        ]);
    }

    /**
     * Complaint number for a patient: the universal pool plus the numbers
     * mapped to the patient's age bracket, cycled by patient index so every
     * complaint (and its diagnosis) appears regularly across the demo set
     * instead of relying on a random draw.
     */
    private function complaintNumberFor(Patient $patient, int $index): int
    {
        // Infants only present with classic baby complaints: fever, feeding
        // upset, cough, sore throat, ear pain - never adult complaints.
        if ((int) $patient->age < 2) {
            $infant = [1, 3, 4, 13, 16, 33];

            return $infant[$index % count($infant)];
        }

        $universal = [1, 2, 3, 4, 5, 8, 10, 11, 12, 13, 14, 16, 19, 21, 25, 26, 29, 30, 31, 33, 35, 36, 37, 38];
        $age = (int) $patient->age;
        $numbers = $universal;

        foreach ([[23, 35, [22, 27, 34]], [36, 55, [9, 20, 32, 39, 40]], [56, 78, [6, 7, 15, 17, 18, 23, 24, 28]]] as [$min, $max, $extra]) {
            if ($age >= $min && $age <= $max) {
                $numbers = array_merge($numbers, $extra);
                break;
            }
        }

        return $numbers[$index % count($numbers)];
    }

    /**
     * Candidate ICD codes for a complaint, age-filtered so young patients are
     * not handed chronic adult diagnoses. Always falls back to URTI.
     */
    private function diagnosisCodeFor(int $complaintNumber, Patient $patient): string
    {
        $candidates = $this->complaintDiagnosisMap()[$complaintNumber] ?? ['J06.9'];

        if ((int) $patient->age < 35) {
            $ageAppropriate = array_values(array_filter(
                $candidates,
                fn (string $code) => ! in_array($code, ['I10', 'E11'], true),
            ));
            $candidates = $ageAppropriate === [] ? ['J06.9'] : $ageAppropriate;
        }

        return $candidates[array_rand($candidates)];
    }

    /**
     * Chief complaints (1-indexed) in Cebuano, matching the age-bracket pool.
     *
     * @return array<int, string>
     */
    private function chiefComplaints(): array
    {
        return [
            1 => 'Gihilantan ug nagpasingot – sugod kagahapon sa buntag',
            2 => 'Gisakit ang tiyan sa tuo nga bahin – sulod na sa tulo ka adlaw',
            3 => 'Nagsuka ug nagkalibanga – sukad pa kagabii',
            4 => 'Gihasol sa ubo nga adunay plema – sulod sa duha ka semana',
            5 => 'Gisakit ang ulo ug gipanglipong – sukad pa sa miaging adlaw',
            6 => 'Naglisod og gininhawa – sulod na sa upat ka oras',
            7 => 'Gisakit ang dughan nga murag gipit-at – sukad pa ganiha',
            8 => 'Katol ug pula ang panit sa tibuok lawas – sulod na sa lima ka adlaw',
            9 => 'Gisakit ang ubos nga bahin sa likod – sulod sa usa ka semana',
            10 => 'Gisakit ug gi-hapdosan ig pangihi – sukad pa kagahapon',
            11 => 'Naghubag ug nagsakit ang tiil – sulod na sa tulo ka adlaw',
            12 => 'Nagdugo ang ilong – sulod sa tulo ka oras',
            13 => 'Sakit ang tutunlan ug naglisod og tulon – sukad pa kagabii',
            14 => 'Gihasol sa uga nga ubo – sulod sa usa ka bulan',
            15 => 'Gihasol sa pagkahalap sa panan-aw – sukad pa sa miaging semana',
            16 => 'Nagsakit ang dunggan ug nagtubig – sulod na sa duha ka adlaw',
            17 => 'Walay gana sa pagkaon ug hilabihan nga kaluya – sulod sa duha ka semana',
            18 => 'Gisakit ang tuhod kon maglakaw – sulod na sa usa ka bulan',
            19 => 'Nagkalibanga ug adunay sagol nga dugo – sukad pa kagahapon sa hapon',
            20 => 'Nagsakit ang abaga ug naglisod og alsa sa kamot – sulod sa tulo ka adlaw',
            21 => 'Sakit ang ngipon ug naghubag ang samag – sukad pa kagabii',
            22 => 'Naglisod og katulog tungod sa kagul-anan ug kabaka – sulod sa tulo ka semana',
            23 => 'Gisakit ang dapit sa dughan – sulod sa duha ka oras',
            24 => 'Nagsakit ang bat-ang ug nagkiang sa paglakaw – sukad pa kagahapon',
            25 => 'Sakit ang dughan kon mag-ubo o maggininhawa og lawom – sulod sa lima ka adlaw',
            26 => 'Gipangbugnaw nga singot ug gipanglipong – sulod sa duha ka oras',
            27 => 'Gisakit ang pus-on – sukad pa kagahapon sa gabii',
            28 => 'Gihasol sa hilabihan nga kalipong nga nagtuyok ang palibot – sulod sa pito ka oras',
            29 => 'Nagdugo ang lagos ug dali rang magpasa ang panit – sulod sa usa ka semana',
            30 => 'Gisakit ang liog ug naglisod og lingi – sukad pa kagahapon sa buntag',
            31 => 'Gihilantan ug gipamaol ang tibuok lawas – sulod sa tulo ka adlaw',
            32 => 'Naghubag ug nagsakit ang tungod sa sapa-sapa sa tiil – sulod sa duha ka adlaw',
            33 => 'Nagsuka-suka ug nagsakit ang tiyan – sukad pa ganiha sa udto',
            34 => 'Sakit ang kilid sa tiyan nga nagpito padulong sa likod – sulod sa lima ka oras',
            35 => 'Gihasol sa hilanat nga pabalik-balik – sulod sa duha ka semana',
            36 => 'Gisakit ang pultahan sa kamot human natagak – sulod sa tulo ka oras',
            37 => 'Naglisod sa pagpangihi ug gi-hapdosan – sukad pa sa miaging adlaw',
            38 => 'Nagsakit ug nagpula ang duha ka mata – sulod sa duha ka adlaw',
            39 => 'Nagsakit ang hawak ug ubos sa likod – sulod sa tulo ka semana',
            40 => 'Gisakit ang dughan human nagbuhat og bug-at – sulod sa usa ka oras',
        ];
    }

    /**
     * Complaint number to ordered candidate ICD codes, drawn from the curated
     * lookup codes verified against the ICD-10 import. Repeated codes are
     * deliberate: they weight the pick, keeping classic barangay conditions
     * (asthma, diabetes) represented in demo morbidity reports.
     *
     * @return array<int, list<string>>
     */
    private function complaintDiagnosisMap(): array
    {
        return [
            1 => ['A90', 'J06.9'],
            2 => ['K52.9', 'A09'],
            3 => ['A09', 'K52.9'],
            4 => ['J06.9', 'J00'],
            5 => ['I10', 'J06.9', 'J00'],
            6 => ['J45.9', 'J45.9', 'J06.9'],
            7 => ['R07.4', 'I10'],
            8 => ['B86'],
            9 => ['M54.5'],
            10 => ['N39.0'],
            11 => ['I10', 'M54.5'],
            12 => ['I10', 'J00'],
            13 => ['J00', 'J06.9'],
            14 => ['J45.9', 'J45.9', 'J06.9'],
            15 => ['E11', 'E11', 'H10.9'],
            16 => ['H66.9'],
            17 => ['E11', 'E11', 'I10', 'J06.9'],
            18 => ['M54.5'],
            19 => ['A09', 'K52.9'],
            20 => ['M54.5'],
            21 => ['K02.9'],
            22 => ['F41.9', 'F41.9', 'I10'],
            23 => ['R07.4', 'I10'],
            24 => ['M54.5'],
            25 => ['R07.4', 'J06.9'],
            26 => ['I10', 'J06.9'],
            27 => ['B86'],
            28 => ['I10'],
            29 => ['A90'],
            30 => ['J00', 'J06.9'],
            31 => ['A90', 'J06.9'],
            32 => ['M54.5', 'B86'],
            33 => ['A09', 'K52.9'],
            34 => ['N39.0'],
            35 => ['A90', 'J06.9'],
            36 => ['T14.1'],
            37 => ['N39.0'],
            38 => ['H10.9'],
            39 => ['M54.5'],
            40 => ['R07.4', 'I10'],
        ];
    }

    /**
     * One prescription set for a diagnosis, using DOH Essential Medicines
     * available at the RHU. Infants get a weight-band-safe set instead of
     * adult tablets.
     *
     * @return list<array{name: string, dosage: string, route: string, frequency: string, duration: string, quantity: int, instructions: string}>
     */
    private function prescriptionItems(string $diagnosisCode, int $age): array
    {
        if ($age < 2) {
            $syrup = $this->rx('Paracetamol 120mg/5mL Syrup', '2.5 mL', 'PO', 'QID (4 times daily)', '3 days', 12,
                'For fever; give every 6 hours as needed. Shake well before use.');

            return match ($diagnosisCode) {
                // Rehydration matters in infant gastroenteritis and dengue.
                'A09', 'K52.9', 'A90' => [
                    $syrup,
                    $this->rx('ORS (Oral Rehydration Salts) Sachet', '1 sachet', 'PO', 'TID (3 times daily)', '3 days', 9,
                        'Give small sips after each loose stool or vomit.'),
                ],
                default => [$syrup],
            };
        }

        $alternatives = match ($diagnosisCode) {
            'J00', 'J06.9' => [
                [$this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                    'For fever and body aches. Rest and drink plenty of fluids.')],
            ],
            'J45.9' => [
                [
                    $this->rx('Salbutamol 100mcg/dose', '2 puffs', 'Inhalation', 'PRN (as needed)', '7 days', 1,
                        'Shake well; inhale deeply for wheezing or shortness of breath.'),
                    $this->rx('Cetirizine 10mg', '1 tab', 'PO', 'OD (once daily)', '7 days', 7,
                        'Take at bedtime to reduce allergy symptoms.'),
                ],
            ],
            'I10' => [
                [$this->rx('Amlodipine 5mg', '1 tab', 'PO', 'OD (once daily)', '30 days', 30,
                    'Take every morning. Monitor blood pressure regularly.')],
                [$this->rx('Losartan 50mg', '1 tab', 'PO', 'OD (once daily)', '30 days', 30,
                    'Take every morning. Avoid salt substitutes high in potassium.')],
            ],
            'E11' => [
                [$this->rx('Metformin 500mg', '1 tab', 'PO', 'BID (2 times daily)', '30 days', 60,
                    'Take after breakfast and dinner.')],
            ],
            'A90' => [
                [
                    $this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '5 days', 15,
                        'For fever only. Avoid ibuprofen, aspirin, or other blood thinners.'),
                    $this->rx('ORS (Oral Rehydration Salts) Sachet', '1 sachet', 'PO', 'TID (3 times daily)', '5 days', 15,
                        'Dissolve one sachet in 1 liter of clean water; sip throughout the day.'),
                ],
            ],
            'A09', 'K52.9' => [
                [
                    $this->rx('ORS (Oral Rehydration Salts) Sachet', '1 sachet', 'PO', 'QID (4 times daily)', '5 days', 20,
                        'Dissolve one sachet in 1 liter of clean water; sip after each loose stool.'),
                    $this->rx('Zinc Sulfate 20mg', '1 tab', 'PO', 'OD (once daily)', '10 days', 10,
                        'Take once daily for 10 days to shorten the illness.'),
                ],
            ],
            'T14.1' => [
                [
                    $this->rx('Povidone-Iodine 10%', 'Apply thinly', 'Topical', 'BID (2 times daily)', '7 days', 14,
                        'Clean the wound with clean running water first, then apply a thin layer.'),
                    $this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                        'For pain. Return if the wound shows signs of infection.'),
                ],
                [
                    $this->rx('Erythromycin Ointment 0.5%', 'Apply thinly', 'Topical', 'BID (2 times daily)', '7 days', 14,
                        'Clean the wound first, then apply a thin layer of ointment.'),
                    $this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                        'For pain. Return if the wound shows signs of infection.'),
                ],
            ],
            'N39.0' => [
                [
                    $this->rx('Amoxicillin 500mg', '1 cap', 'PO', 'TID (3 times daily)', '5 days', 15,
                        'Complete the full course even if symptoms improve early.'),
                    $this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                        'For pain and discomfort.'),
                ],
                [$this->rx('Ciprofloxacin 500mg', '1 tab', 'PO', 'BID (2 times daily)', '3 days', 6,
                    'Complete the full course; take with a full glass of water.')],
            ],
            'H10.9' => [
                [$this->rx('Erythromycin Ointment 0.5%', 'Thin strip', 'Ophthalmic', 'QID (4 times daily)', '5 days', 1,
                    'Apply a thin strip to the affected eye four times daily; do not share the tube.')],
            ],
            'H66.9' => [
                [
                    $this->rx('Amoxicillin 500mg', '1 cap', 'PO', 'TID (3 times daily)', '5 days', 15,
                        'Complete the full course even if symptoms improve early.'),
                    $this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                        'For ear pain and fever.'),
                ],
            ],
            'K02.9' => [
                [
                    $this->rx('Amoxicillin 500mg', '1 cap', 'PO', 'TID (3 times daily)', '5 days', 15,
                        'Complete the full course; see a dentist if pain persists.'),
                    $this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                        'For dental pain.'),
                ],
            ],
            'B86' => [
                [$this->rx('Permethrin 5% Cream', 'Apply thinly', 'Topical', 'Single application', '7 days', 1,
                    'Apply to clean, dry skin; leave 8 to 14 hours then wash off. Repeat once after 7 days.')],
            ],
            'M54.5' => [
                [$this->rx('Ibuprofen 400mg', '1 tab', 'PO', 'TID (3 times daily)', '5 days', 15,
                    'Take after meals. Rest and avoid heavy lifting.')],
            ],
            'R07.4' => [
                [$this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                    'For chest discomfort. Seek immediate care if pain worsens or spreads to the arm or jaw.')],
                [$this->rx('Amlodipine 5mg', '1 tab', 'PO', 'OD (once daily)', '30 days', 30,
                    'Take every morning. Monitor blood pressure regularly.')],
            ],
            'F41.9' => [
                [$this->rx('Cetirizine 10mg', '1 tab', 'PO', 'OD (once daily)', '7 days', 7,
                    'Take at bedtime; may cause drowsiness. Practice regular sleep habits.')],
            ],
            default => [
                [$this->rx('Paracetamol 500mg', '1 tab', 'PO', 'TID (3 times daily)', '3 days', 9,
                    'For fever and body aches. Return if symptoms persist.')],
            ],
        };

        return $alternatives[array_rand($alternatives)];
    }

    /**
     * @return array{name: string, dosage: string, route: string, frequency: string, duration: string, quantity: int, instructions: string}
     */
    private function rx(
        string $name,
        string $dosage,
        string $route,
        string $frequency,
        string $duration,
        int $quantity,
        string $instructions,
    ): array {
        return compact('name', 'dosage', 'route', 'frequency', 'duration', 'quantity', 'instructions');
    }

    /**
     * Clinic-hours timestamp for a day offset; falls back to "just now"
     * when today's clinic hours have not happened yet.
     */
    private function timestampFor(int $daysAgo): Carbon
    {
        $at = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 16), rand(0, 59));

        return $at->isFuture() ? Carbon::now()->subMinutes(rand(10, 240)) : $at;
    }

    /** Minutes after a consultation start, clamped so nothing lands in the future. */
    private function after(Carbon $base, int $minutes): Carbon
    {
        $at = $base->copy()->addMinutes($minutes);

        return $at->isFuture() ? Carbon::now() : $at;
    }

    private function daysSince(mixed $date): int
    {
        if (! $date instanceof Carbon) {
            return 0;
        }

        // Carbon 3 diffs are signed; measure from the stored date forward to today.
        return max(0, (int) $date->copy()->startOfDay()->diffInDays(Carbon::now()->startOfDay()));
    }
}
