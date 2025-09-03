<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Flow;
use App\Models\FlowStep;
use App\Models\FlowStepHistory;
use App\Models\FlowTag;
use App\Models\Company;

class FlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Buscar a empresa EverAdapt (deve ter sido criada pelo CompanySeeder)
        $company = Company::where('company', 'EverAdapt')->first();
        
        if (!$company) {
            $this->command->error('EverAdapt company not found. Please run CompanySeeder first.');
            return;
        }
        
        $this->command->info('Using company: ' . $company->company . ' (ID: ' . $company->id . ')');
        
        // Create a single flow
        $flow = Flow::create([
            'name' => 'Sample Data Processing Flow',
            'note' => 'A complete data processing workflow with validation and export',
            'active' => true,
            'user_id' => 1,
            'company_id' => $company->id,
        ]);

        $this->command->info('Flow created with ID: ' . $flow->_id);

        // Create steps for this flow using the actual MongoDB ObjectId
        $steps = [
            [
                'name' => 'Validate Input Data',
                'expression' => 'IF (@input_data != "") THEN validate_data(@input_data) ENDIF',
                'order' => 1,
                'active' => true,
                'flow_id' => $flow->_id,
                'user_id' => 1,
            ],
            [
                'name' => 'Transform Data',
                'expression' => 'FOREACH item IN @validated_data , transform_item(item) NEXT',
                'order' => 2,
                'active' => true,
                'flow_id' => $flow->_id,
                'user_id' => 1,
            ],
            [
                'name' => 'Apply Business Rules',
                'expression' => 'apply_business_rules(@transformed_data)',
                'order' => 3,
                'active' => true,
                'flow_id' => $flow->_id,
                'user_id' => 1,
            ],
            [
                'name' => 'Export Results',
                'expression' => 'EXPORT(@processed_data, "output", "results", "standard", 1, "Processed Data")',
                'order' => 4,
                'active' => true,
                'flow_id' => $flow->_id,
                'user_id' => 1,
            ],
        ];

        $createdSteps = [];
        foreach ($steps as $stepData) {
            $step = FlowStep::create($stepData);
            $createdSteps[] = $step;
            $this->command->info('Step created: ' . $step->name . ' (ID: ' . $step->_id . ')');
            
            // Create initial version snapshot for steps with expressions
            if (!empty($step->expression)) {
                FlowStepHistory::createSnapshot(
                    $step,
                    'create',
                    'Initial version created by seeder',
                    1
                );
                $this->command->info('  → Version history created for: ' . $step->name);
            }
        }
        
        // Create some sample version history for demonstration
        if (count($createdSteps) > 0) {
            $this->command->info('Creating sample version history...');
            
            // Update first step to create version history
            $firstStep = $createdSteps[0];
            $firstStep->update([
                'expression' => 'IF (@input_data != "") THEN validate_data(@input_data) AND check_format(@input_data) ENDIF'
            ]);
            
            FlowStepHistory::createSnapshot(
                $firstStep->fresh(),
                'update',
                'Added format validation to expression',
                1
            );
            
            // Update second step
            $secondStep = $createdSteps[1];
            $secondStep->update([
                'expression' => 'FOREACH item IN @validated_data , transform_item(item) AND log_transformation(item) NEXT'
            ]);
            
            FlowStepHistory::createSnapshot(
                $secondStep->fresh(),
                'update',
                'Added logging to transformation process',
                1
            );
            
            $this->command->info('Sample version history created successfully!');
        }
    }
}
