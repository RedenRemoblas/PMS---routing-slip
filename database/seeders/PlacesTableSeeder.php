<?php

namespace Database\Seeders;

use App\Models\Setup\Place;
use Illuminate\Database\Seeder;

class PlacesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $places = [
            // Cordillera Administrative Region (CAR)
            ['name' => 'Bangued, Abra', 'latitude' => 17.5931, 'longitude' => 120.6193],
            ['name' => 'Boliney, Abra', 'latitude' => 17.5485, 'longitude' => 120.7868],
            ['name' => 'Bucay, Abra', 'latitude' => 17.5437, 'longitude' => 120.6664],
            ['name' => 'Bucloc, Abra', 'latitude' => 17.5609, 'longitude' => 120.7662],
            ['name' => 'Daguioman, Abra', 'latitude' => 17.5561, 'longitude' => 120.8464],
            ['name' => 'Danglas, Abra', 'latitude' => 17.6989, 'longitude' => 120.6320],
            ['name' => 'Dolores, Abra', 'latitude' => 17.5693, 'longitude' => 120.6524],
            ['name' => 'La Paz, Abra', 'latitude' => 17.6363, 'longitude' => 120.6788],
            ['name' => 'Lacub, Abra', 'latitude' => 17.6703, 'longitude' => 120.7370],
            ['name' => 'Lagangilang, Abra', 'latitude' => 17.6290, 'longitude' => 120.7367],
            ['name' => 'Lagayan, Abra', 'latitude' => 17.6709, 'longitude' => 120.7323],
            ['name' => 'Langiden, Abra', 'latitude' => 17.5931, 'longitude' => 120.6528],
            ['name' => 'Licuan-Baay, Abra', 'latitude' => 17.6833, 'longitude' => 120.7667],
            ['name' => 'Luba, Abra', 'latitude' => 17.5408, 'longitude' => 120.7692],
            ['name' => 'Malibcong, Abra', 'latitude' => 17.6833, 'longitude' => 120.7667],
            ['name' => 'Manabo, Abra', 'latitude' => 17.4768, 'longitude' => 120.7029],
            ['name' => 'Peñarrubia, Abra', 'latitude' => 17.5836, 'longitude' => 120.6844],
            ['name' => 'Pidigan, Abra', 'latitude' => 17.5811, 'longitude' => 120.6089],
            ['name' => 'Pilar, Abra', 'latitude' => 17.5171, 'longitude' => 120.6155],
            ['name' => 'Sallapadan, Abra', 'latitude' => 17.5631, 'longitude' => 120.7178],
            ['name' => 'San Isidro, Abra', 'latitude' => 17.5836, 'longitude' => 120.6083],
            ['name' => 'San Juan, Abra', 'latitude' => 17.5501, 'longitude' => 120.7217],
            ['name' => 'San Quintin, Abra', 'latitude' => 17.6270, 'longitude' => 120.6959],
            ['name' => 'Tayum, Abra', 'latitude' => 17.6069, 'longitude' => 120.6467],
            ['name' => 'Tineg, Abra', 'latitude' => 17.7464, 'longitude' => 120.7626],
            ['name' => 'Tubo, Abra', 'latitude' => 17.3859, 'longitude' => 120.8751],
            ['name' => 'Villaviciosa, Abra', 'latitude' => 17.5691, 'longitude' => 120.6797],

            // Apayao
            ['name' => 'Calanasan, Apayao', 'latitude' => 18.2697, 'longitude' => 121.1644],
            ['name' => 'Conner, Apayao', 'latitude' => 17.8735, 'longitude' => 121.2919],
            ['name' => 'Flora, Apayao', 'latitude' => 18.1958, 'longitude' => 121.3739],
            ['name' => 'Kabugao, Apayao', 'latitude' => 18.0085, 'longitude' => 121.2138],
            ['name' => 'Luna, Apayao', 'latitude' => 18.3571, 'longitude' => 121.3139],
            ['name' => 'Pudtol, Apayao', 'latitude' => 18.3297, 'longitude' => 121.3494],
            ['name' => 'Santa Marcela, Apayao', 'latitude' => 18.3864, 'longitude' => 121.3922],

            // Benguet
            ['name' => 'Atok, Benguet', 'latitude' => 16.5903, 'longitude' => 120.6722],
            ['name' => 'Bakun, Benguet', 'latitude' => 16.7684, 'longitude' => 120.7431],
            ['name' => 'Bokod, Benguet', 'latitude' => 16.5642, 'longitude' => 120.7961],
            ['name' => 'Buguias, Benguet', 'latitude' => 16.7749, 'longitude' => 120.8419],
            ['name' => 'Itogon, Benguet', 'latitude' => 16.3872, 'longitude' => 120.7106],
            ['name' => 'Kabayan, Benguet', 'latitude' => 16.6172, 'longitude' => 120.8439],
            ['name' => 'Kapangan, Benguet', 'latitude' => 16.5544, 'longitude' => 120.6478],
            ['name' => 'Kibungan, Benguet', 'latitude' => 16.6939, 'longitude' => 120.6475],
            ['name' => 'La Trinidad, Benguet', 'latitude' => 16.4550, 'longitude' => 120.5871],
            ['name' => 'Mankayan, Benguet', 'latitude' => 16.8503, 'longitude' => 120.7859],
            ['name' => 'Sablan, Benguet', 'latitude' => 16.4742, 'longitude' => 120.4783],
            ['name' => 'Tuba, Benguet', 'latitude' => 16.3406, 'longitude' => 120.5714],
            ['name' => 'Tublay, Benguet', 'latitude' => 16.5177, 'longitude' => 120.6462],

            // Ifugao
            ['name' => 'Aguinaldo, Ifugao', 'latitude' => 16.9000, 'longitude' => 121.1833],
            ['name' => 'Alfonso Lista, Ifugao', 'latitude' => 16.9833, 'longitude' => 121.5000],
            ['name' => 'Asipulo, Ifugao', 'latitude' => 16.7267, 'longitude' => 121.1664],
            ['name' => 'Banaue, Ifugao', 'latitude' => 16.9128, 'longitude' => 121.0678],
            ['name' => 'Hingyon, Ifugao', 'latitude' => 16.8417, 'longitude' => 121.0983],
            ['name' => 'Hungduan, Ifugao', 'latitude' => 16.8453, 'longitude' => 121.0117],
            ['name' => 'Kiangan, Ifugao', 'latitude' => 16.7983, 'longitude' => 121.0994],
            ['name' => 'Lagawe, Ifugao', 'latitude' => 16.7917, 'longitude' => 121.1086],
            ['name' => 'Lamut, Ifugao', 'latitude' => 16.7197, 'longitude' => 121.2792],
            ['name' => 'Mayoyao, Ifugao', 'latitude' => 16.9333, 'longitude' => 121.1833],
            ['name' => 'Tinoc, Ifugao', 'latitude' => 16.6753, 'longitude' => 120.8597],

            // Kalinga
            ['name' => 'Balbalan, Kalinga', 'latitude' => 17.4519, 'longitude' => 121.1903],
            ['name' => 'Lubuagan, Kalinga', 'latitude' => 17.3667, 'longitude' => 121.2167],
            ['name' => 'Pasil, Kalinga', 'latitude' => 17.4433, 'longitude' => 121.1583],
            ['name' => 'Pinukpuk, Kalinga', 'latitude' => 17.5333, 'longitude' => 121.3333],
            ['name' => 'Rizal (Liwan), Kalinga', 'latitude' => 17.5500, 'longitude' => 121.5500],
            ['name' => 'Tabuk City, Kalinga', 'latitude' => 17.4147, 'longitude' => 121.4467],
            ['name' => 'Tanudan, Kalinga', 'latitude' => 17.4000, 'longitude' => 121.2000],
            ['name' => 'Tinglayan, Kalinga', 'latitude' => 17.3167, 'longitude' => 121.1500],

            // Mountain Province
            ['name' => 'Barlig, Mountain Province', 'latitude' => 17.0789, 'longitude' => 121.0883],
            ['name' => 'Bauko, Mountain Province', 'latitude' => 17.0861, 'longitude' => 120.8494],
            ['name' => 'Besao, Mountain Province', 'latitude' => 17.0594, 'longitude' => 120.7819],
            ['name' => 'Bontoc, Mountain Province', 'latitude' => 17.0900, 'longitude' => 120.9752],
            ['name' => 'Natonin, Mountain Province', 'latitude' => 17.0789, 'longitude' => 121.2794],
            ['name' => 'Paracelis, Mountain Province', 'latitude' => 17.0833, 'longitude' => 121.3833],
            ['name' => 'Sabangan, Mountain Province', 'latitude' => 16.9992, 'longitude' => 120.9114],
            ['name' => 'Sadanga, Mountain Province', 'latitude' => 17.0833, 'longitude' => 121.1667],
            ['name' => 'Sagada, Mountain Province', 'latitude' => 17.0833, 'longitude' => 120.9000],
            ['name' => 'Tadian, Mountain Province', 'latitude' => 16.9556, 'longitude' => 120.8433],

            // Region 1 (Ilocos Region)
            ['name' => 'San Fernando City, La Union', 'latitude' => 16.6158, 'longitude' => 120.3191],
            ['name' => 'Laoag City, Ilocos Norte', 'latitude' => 18.1978, 'longitude' => 120.5927],
            ['name' => 'Vigan City, Ilocos Sur', 'latitude' => 17.5746, 'longitude' => 120.3866],
            ['name' => 'Dagupan City, Pangasinan', 'latitude' => 16.0431, 'longitude' => 120.3333],
            // Add all other municipalities from Region 1...

            // Region 2 (Cagayan Valley)
            ['name' => 'Tuguegarao City, Cagayan', 'latitude' => 17.6131, 'longitude' => 121.7264],
            ['name' => 'Ilagan City, Isabela', 'latitude' => 17.1486, 'longitude' => 121.8890],
            ['name' => 'Bayombong, Nueva Vizcaya', 'latitude' => 16.4864, 'longitude' => 121.1527],
            ['name' => 'Basco, Batanes', 'latitude' => 20.4483, 'longitude' => 121.9700],
            // Add all other municipalities from Region 2...

            // Region 3 (Central Luzon)
            ['name' => 'San Fernando City, Pampanga', 'latitude' => 15.0343, 'longitude' => 120.6844],
            ['name' => 'Balanga City, Bataan', 'latitude' => 14.6761, 'longitude' => 120.5364],
            ['name' => 'Tarlac City, Tarlac', 'latitude' => 15.4802, 'longitude' => 120.5977],
            ['name' => 'Cabanatuan City, Nueva Ecija', 'latitude' => 15.4868, 'longitude' => 120.9675],
            // Add all other municipalities from Region 3...
        ];

        foreach ($places as $place) {
            Place::create($place);
        }
    }
}
