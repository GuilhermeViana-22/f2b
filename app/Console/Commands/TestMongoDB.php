<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Selection;
use Illuminate\Support\Facades\DB;

class TestMongoDB extends Command
{
    protected $signature = 'test:mongodb';
    protected $description = 'Test MongoDB connection and operations';

    public function handle()
    {
        $this->info('Testing MongoDB connection...');
        
        try {
            // Test basic connection
            $connection = DB::connection('mongodb');
            $this->info('✓ MongoDB connection established');
            
            // Test database ping
            $result = $connection->getMongoClient()->selectDatabase('f2b')->command(['ping' => 1]);
            $this->info('✓ MongoDB ping successful');
            
            // Test creating a selection
            $selection = new Selection();
            $selection->title = 'Test from command';
            $selection->jobNo = 'TEST-001';
            $selection->client = 'Test Client';
            $selection->save();
            
            $this->info('✓ Selection created with ID: ' . $selection->_id);
            
            // Test fetching the selection
            $fetchedSelection = Selection::find($selection->_id);
            if ($fetchedSelection) {
                $this->info('✓ Selection retrieved: ' . $fetchedSelection->title);
            }
            
            // Clean up
            $selection->delete();
            $this->info('✓ Test selection deleted');
            
            $this->info('🎉 All MongoDB tests passed!');
            
        } catch (\Exception $e) {
            $this->error('❌ MongoDB test failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
