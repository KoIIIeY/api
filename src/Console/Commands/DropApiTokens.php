<?php

namespace Koiiiey\Api\Console\Commands;

use App\Token;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DropApiTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drop:api_tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drops api tokens week old';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tokens = Token::where('updated_at', '<', Carbon::now()->subWeek(1)->toDateTimeString())->get();
        foreach ($tokens as $token) {
            $token->delete();
        }
    }
}
