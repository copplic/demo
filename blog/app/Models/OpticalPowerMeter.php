<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpticalPowerMeter extends Model
{
    //
    protected $table = 'optical_power_meter';

    protected $fillable=['serial_num','name','wavelength','dbm','ref','mode','uid'];
}
