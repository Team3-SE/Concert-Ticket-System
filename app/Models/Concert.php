<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Concert
 * manage Concert entity and its relationships
 * Author : AI Agent
 * Created : 30/08/2026
 */
class Concert extends Model
{
    use HasUlids;

    protected $table = 'concert';
    protected $primaryKey = 'concert_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'organizer_id',
        'concert_code',
        'name',
        'artist',
        'event_datetime',
        'description',
        'poster_path',
        'seat_map_image_path',
        'sale_start_datetime',
        'sale_end_datetime',
        'max_ticket_per_order',
        'status',
        'venue',
    ];

    /**
     * getMockConcerts()
     * Return mock data for testing the model tester UI.
     * param : none
     * return : Array
     */
    public static function getMockConcerts()
    {
        return [
            [
                'concert_id' => 'c1-123',
                'name' => 'Mock Concert 2026',
                'artist' => 'The Mockers',
                'event_datetime' => '2026-12-31 20:00:00',
                'venue' => 'Mock Arena'
            ],
            [
                'concert_id' => 'c1-124',
                'name' => 'Symphony of Mocks',
                'artist' => 'Orchestra',
                'event_datetime' => '2027-01-15 19:00:00',
                'venue' => 'Grand Hall'
            ]
        ];
    }

    /**
     * getConcertInfo()
     * Return formatted info for a specific instance.
     * param : none
     * return : String
     */
    public function getConcertInfo()
    {
        return "{$this->name} by {$this->artist} at {$this->venue} on {$this->event_datetime}";
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
