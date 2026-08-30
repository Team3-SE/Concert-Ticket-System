<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Concert
 * manage Concert entity and its relationships
 * Author : AI Agent
 * Created : 30/08/2026
 */
class Concert extends Model
{
    use HasUuids;

    protected $table = 'cts_concerts';
    protected $primaryKey = 'concert_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'organizer_id',
        'name',
        'artist',
        'date_time',
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
                'date_time' => '2026-12-31 20:00:00',
                'venue' => 'Mock Arena'
            ],
            [
                'concert_id' => 'c1-124',
                'name' => 'Symphony of Mocks',
                'artist' => 'Orchestra',
                'date_time' => '2027-01-15 19:00:00',
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
        return "{$this->name} by {$this->artist} at {$this->venue} on {$this->date_time}";
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
