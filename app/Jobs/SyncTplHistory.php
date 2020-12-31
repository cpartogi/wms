<?php

namespace App\Jobs;

use App\Http\Services\ThirdPartyLogistic\Jne;
use App\Http\Services\ThirdPartyLogistic\Jnt;
use App\Http\Services\ThirdPartyLogistic\Sicepat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTplHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $orders;
    
    /**
     * Create a new job instance.
     *
     * @param Collection $orders
     */
    public function __construct(Collection $orders)
    {
        $this->orders = $orders;
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->orders as $order) {
            SyncSingleTplHistory::dispatch($order);
        }
    
        return true;
    }
}
