<?php

namespace Database\Seeders;

use App\Enums\ConsultationStatus;
use App\Models\HealthWorker;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsultationSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::query()->orderBy('id')->get();
        $healthWorkers = HealthWorker::all();

        if ($patients->isEmpty() || $healthWorkers->isEmpty()) {
            $this->command?->warn('Please run PatientSeeder and ensure health workers exist before running ConsultationSeeder.');

            return;
        }

        $nurse = $healthWorkers->firstWhere('role', 'Nurse') ?? $healthWorkers->first();
        $doctor = $healthWorkers->firstWhere('role', 'Doctor') ?? $healthWorkers->first();
        $encoder = $healthWorkers->firstWhere('role', 'BHW') ?? $nurse;

        // Chief complaints are 1-indexed so the numbers match the age-bracket mapping below.
        $chiefComplaints = [
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

        // Age-bracket complaint mapping - every complaint number appears exactly once:
        // Universal pool (all ages) plus the bracket matching the patient's age.
        $universalComplaints = [1, 2, 3, 4, 5, 8, 10, 11, 12, 13, 14, 16, 19, 21, 25, 26, 29, 30, 31, 33, 35, 36, 37, 38];
        $ageBracketComplaints = [
            [23, 35, [22, 27, 34]],
            [36, 55, [9, 20, 32, 39, 40]],
            [56, 78, [6, 7, 15, 17, 18, 23, 24, 28]],
        ];
        $modeOfTransaction = ['Walk-in', 'Visited', 'Referral'];

        $consultations = [];
        $referredIndexes = [];

        // Exact status distribution across the whole seed set:
        // 1 nurse_review, 1 doctor_review, 1 referred, everything else completed.
        $firstConsultationStatuses = array_fill(0, $patients->count(), ConsultationStatus::Completed->value);
        $firstConsultationStatuses[0] = ConsultationStatus::NurseReview->value;
        $firstConsultationStatuses[1] = ConsultationStatus::DoctorReview->value;
        $firstConsultationStatuses[2] = ConsultationStatus::Referred->value;

        foreach ($patients as $index => $patient) {
            $complaintPool = $this->complaintPoolFor($patient, $chiefComplaints, $universalComplaints, $ageBracketComplaints);
            $statuses = [
                $firstConsultationStatuses[$index],
                ConsultationStatus::Completed->value,
            ];

            foreach ($statuses as $status) {
                if ($status === ConsultationStatus::Referred->value) {
                    $referredIndexes[] = count($consultations);
                }

                $consultations[] = $this->consultationPayload(
                    $patient,
                    $encoder,
                    $nurse,
                    $doctor,
                    $status,
                    $complaintPool,
                    $modeOfTransaction,
                );
            }
        }

        $this->insertConsultationsWithReferrals($consultations, $referredIndexes);

        $this->command?->info('Consultations seeded successfully: '.count($consultations).' records created.');
    }

    /**
     * Build the chief-complaint pool for a patient: the universal list plus the
     * complaints mapped to the patient's age bracket (universal only when the
     * age falls outside 23-78).
     *
     * @param  array<int, string>  $chiefComplaints
     * @param  list<int>  $universalComplaints
     * @param  list<array{0: int, 1: int, 2: list<int>}>  $ageBracketComplaints  [minAge, maxAge, complaint numbers]
     * @return list<string>
     */
    private function complaintPoolFor(
        Patient $patient,
        array $chiefComplaints,
        array $universalComplaints,
        array $ageBracketComplaints,
    ): array {
        $age = (int) $patient->age;
        $numbers = $universalComplaints;

        foreach ($ageBracketComplaints as [$minAge, $maxAge, $bracketNumbers]) {
            if ($age >= $minAge && $age <= $maxAge) {
                $numbers = array_merge($numbers, $bracketNumbers);
                break;
            }
        }

        return array_values(array_map(fn (int $number) => $chiefComplaints[$number], $numbers));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function consultationPayload(
        Patient $patient,
        HealthWorker $encoder,
        HealthWorker $nurse,
        HealthWorker $doctor,
        string $status,
        array $complaintPool,
        array $modeOfTransaction,
    ): array {
        $createdAt = Carbon::now()->subDays(rand(1, 180));
        $nurseValidated = in_array($status, [
            ConsultationStatus::DoctorReview->value,
            ConsultationStatus::Completed->value,
            ConsultationStatus::Referred->value,
        ], true);

        $data = [
            'patient_id' => $patient->id,
            'status' => $status,
            'nurse_validated_at' => $nurseValidated ? $createdAt->copy()->addMinutes(rand(30, 240)) : null,
            'nurse_validated_by' => $nurseValidated ? $nurse->id : null,
            'attending_doctor_id' => in_array($status, [
                ConsultationStatus::Completed->value,
                ConsultationStatus::Referred->value,
            ], true) ? $doctor->id : null,
            'is_locked' => in_array($status, [
                ConsultationStatus::Completed->value,
                ConsultationStatus::Referred->value,
            ], true),
            'complaint_text' => $complaintPool[array_rand($complaintPool)],
            'nature_of_visit' => rand(0, 1) ? 'Follow-up' : 'Initial Consultation',
            'notes' => 'Patient presented with symptoms. Advised on home care and follow-up.',
            'mode_of_transaction' => $status === ConsultationStatus::Referred->value
                ? 'Referral'
                : $modeOfTransaction[array_rand($modeOfTransaction)],
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addMinutes(rand(0, 60)),
        ];

        // The worker who handled the intake differs per stage of the workflow:
        // nurse handles consultations awaiting review, the BHW encoder handles the rest.
        $data['worker_id'] = in_array($status, [
            ConsultationStatus::NurseReview->value,
            ConsultationStatus::DoctorReview->value,
        ], true) ? $nurse->id : $encoder->id;

        if ($status === ConsultationStatus::Referred->value) {
            $data['referred_from'] = 'Barangay Health Center';
        }

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $consultations
     * @param  list<int>  $referredIndexes  Flat consultation indexes that need an outward referral
     */
    private function insertConsultationsWithReferrals(array $consultations, array $referredIndexes): void
    {
        $referred = array_flip($referredIndexes);

        foreach ($consultations as $index => $row) {
            $consultationId = DB::table('consultations')->insertGetId($row);

            if (! isset($referred[$index])) {
                continue;
            }

            $createdAt = $row['created_at'];
            DB::table('outward_referrals')->insert([
                'consultation_id' => $consultationId,
                'destination_facility' => 'Tagoloan District Hospital',
                'pertinent_history' => $row['notes'] ?? $row['complaint_text'],
                'actions_taken' => 'Initial assessment and supportive care given.',
                'specific_details' => 'Referred for specialist consultation.',
                'status' => 'pending',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
