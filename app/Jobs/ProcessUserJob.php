<?php

namespace App\Jobs;

use App\Models\CreditCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        $dateOfBirthString = $this->item['date_of_birth'];

        $dateOfBirth = null;

        if ($dateOfBirthString !== null) {
            if (strpos($dateOfBirthString, 'T') !== false) {
                $dateOfBirth = Carbon::parse($dateOfBirthString);
            } elseif (strpos($dateOfBirthString, '-') !== false) {
                $dateOfBirth = Carbon::createFromFormat('Y-m-d H:i:s', $dateOfBirthString);
            } else {
                $dateOfBirth = Carbon::createFromFormat('d/m/Y', $dateOfBirthString);
            }

            if ($dateOfBirth instanceof Carbon) {
                $age = $dateOfBirth->diffInYears(Carbon::now());

                if ($age < 18 || $age > 65) {
                    return;
                }
            } else {
                return;
            }
        }

        $description = str_replace('<br>', '', $this->item['description']);

        $user = User::create([
            'name' => $this->item['name'],
            'address' => $this->item['address'],
            'checked' => $this->item['checked'],
            'description' => $description,
            'interest' => $this->item['interest'],
            'date_of_birth' => $dateOfBirth ? $dateOfBirth->format('d-m-Y') : null,
            'email' => $this->item['email'],
            'account' => $this->item['account'],
        ]);

        $creditCard = CreditCard::create([
            'user_id' => $user->id,
            'type' => $this->item['credit_card']['type'],
            'number' => $this->item['credit_card']['number'],
            'name' => $this->item['credit_card']['name'],
            'expiration_date' => $this->item['credit_card']['expirationDate'],
        ]);
    }
}
