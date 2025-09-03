<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MongoDB\Laravel\Connection;
use Illuminate\Support\Facades\DB;

class SetupFlowIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flows:setup-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MongoDB indexes for Flow, FlowStep, and FlowTag collections';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $connection = DB::connection('mongodb');
            
            // Create indexes for flows collection
            $this->info('Creating indexes for flows collection...');
            $connection->getCollection('flows')->createIndex(['user_id' => 1]);
            $connection->getCollection('flows')->createIndex(['name' => 1]);
            $connection->getCollection('flows')->createIndex(['created_at' => -1]);
            $connection->getCollection('flows')->createIndex(['user_id' => 1, 'name' => 1]);
            
            // Create indexes for flow_steps collection
            $this->info('Creating indexes for flow_steps collection...');
            $connection->getCollection('flow_steps')->createIndex(['flow_id' => 1]);
            $connection->getCollection('flow_steps')->createIndex(['order' => 1]);
            $connection->getCollection('flow_steps')->createIndex(['flow_id' => 1, 'order' => 1]);
            
            // Create indexes for flow_tags collection
            $this->info('Creating indexes for flow_tags collection...');
            $connection->getCollection('flow_tags')->createIndex(['name' => 1]);
            $connection->getCollection('flow_tags')->createIndex(['user_id' => 1]);
            $connection->getCollection('flow_tags')->createIndex(['user_id' => 1, 'name' => 1]);
            
            $this->info('All flow indexes created successfully!');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create indexes: ' . $e->getMessage());
            return 1;
        }
    }
}
