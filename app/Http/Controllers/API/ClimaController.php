<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ClimaController extends Controller
{
    private $apiKey;
    private $ciudad;

    public function __construct()
    {
        $this->apiKey = env('OPENWEATHER_API_KEY', '');
        $this->ciudad = 'Mexico City'; // Cambia por tu ciudad
    }

    
    public function actual()
    {
       
        if (empty($this->apiKey)) {
            return response()->json($this->climaSimulado());
        }

        try {
            $url = "https://api.openweathermap.org/data/2.5/weather?q={$this->ciudad}&appid={$this->apiKey}&units=metric&lang=es";
            
            $response = Http::get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'ciudad' => $data['name'],
                    'temperatura' => round($data['main']['temp']) . '°C',
                    'sensacion' => round($data['main']['feels_like']) . '°C',
                    'descripcion' => ucfirst($data['weather'][0]['description']),
                    'icono' => 'https://openweathermap.org/img/wn/' . $data['weather'][0]['icon'] . '@2x.png',
                    'humedad' => $data['main']['humidity'] . '%',
                    'recomendacion' => $this->recomendacion($data['weather'][0]['main']),
                ]);
            }
        } catch (\Exception $e) {
            
        }

        return response()->json($this->climaSimulado());
    }

   
    public function pronostico()
    {
        if (empty($this->apiKey)) {
            return response()->json($this->pronosticoSimulado());
        }

        try {
            $url = "https://api.openweathermap.org/data/2.5/forecast?q={$this->ciudad}&appid={$this->apiKey}&units=metric&lang=es";
            
            $response = Http::get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                $dias = [];

                foreach ($data['list'] as $item) {
                    $fecha = date('Y-m-d', strtotime($item['dt_txt']));
                    if (!isset($dias[$fecha])) {
                        $dias[$fecha] = [
                            'fecha' => $fecha,
                            'temp_max' => round($item['main']['temp_max']) . '°C',
                            'temp_min' => round($item['main']['temp_min']) . '°C',
                            'descripcion' => ucfirst($item['weather'][0]['description']),
                            'icono' => 'https://openweathermap.org/img/wn/' . $item['weather'][0]['icon'] . '.png',
                            'lluvia' => isset($item['rain']['3h']) ? $item['rain']['3h'] . 'mm' : '0mm',
                            'recomendable' => $item['weather'][0]['main'] !== 'Rain',
                        ];
                    }
                }

                return response()->json(array_values(array_slice($dias, 0, 5)));
            }
        } catch (\Exception $e) {
            // Si falla, devolver datos simulados
        }

        return response()->json($this->pronosticoSimulado());
    }

    
    private function climaSimulado()
    {
        $climas = ['Soleado', 'Nublado', 'Parcialmente nublado', 'Despejado'];
        $clima = $climas[array_rand($climas)];
        $temp = rand(20, 32);
        
        return [
            'success' => true,
            'ciudad' => 'Tu Ciudad',
            'temperatura' => $temp . '°C',
            'sensacion' => ($temp - 2) . '°C',
            'descripcion' => $clima,
            'icono' => 'https://openweathermap.org/img/wn/01d@2x.png',
            'humedad' => rand(30, 70) . '%',
            'recomendacion' => ' Clima favorable para citas',
        ];
    }

    /**
     * Pronóstico simulado
     */
    private function pronosticoSimulado()
    {
        $dias = [];
        $climas = ['Soleado', 'Nublado', 'Parcialmente nublado', 'Despejado', 'Lluvia ligera'];
        
        for ($i = 0; $i < 5; $i++) {
            $fecha = date('Y-m-d', strtotime("+{$i} days"));
            $clima = $climas[array_rand($climas)];
            $tempMax = rand(25, 33);
            $tempMin = rand(15, 22);
            
            $dias[] = [
                'fecha' => $fecha,
                'temp_max' => $tempMax . '°C',
                'temp_min' => $tempMin . '°C',
                'descripcion' => $clima,
                'icono' => 'https://openweathermap.org/img/wn/01d.png',
                'lluvia' => $clima === 'Lluvia ligera' ? rand(1, 5) . 'mm' : '0mm',
                'recomendable' => $clima !== 'Lluvia ligera',
            ];
        }
        
        return $dias;
    }

    private function recomendacion($clima)
    {
        switch ($clima) {
            case 'Rain': return ' Lluvia: Recomendamos agendar bajo techo';
            case 'Clear': return ' ¡Excelente día para traer tu moto!';
            case 'Clouds': return ' Clima aceptable';
            default: return ' Clima favorable';
        }
    }
}