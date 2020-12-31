<?php

namespace App\Jobs;

use App\Http\Services\ThirdPartyLogistic\Jne;
use App\Http\Services\ThirdPartyLogistic\Jnt;
use App\Http\Services\ThirdPartyLogistic\Sicepat;
use App\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSingleTplHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $order;
    
    /**
     * Create a new job instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jne     = new Jne();
        $jnt     = new Jnt();
        $sicepat = new Sicepat();
        
        if (string_contains($this->order->courier, "jne")) {
            $jne->synchronizeHistory($this->order->id);
        } else if (string_contains($this->order->courier, "jnt")) {
            $jnt->synchronizeHistory($this->order->id);
        } else if (string_contains($this->order->courier, "j&t")) {
            $jnt->synchronizeHistory($this->order->id);
        } else if (string_contains($this->order->courier, "cepat")) {
            $sicepat->synchronizeHistory($this->order->id);
        }
    
        return true;
    }
}
