<?php

$config['arrChartData'] = [
    "type" => "bar",
    "options" => [
        'plugins' => [
            'datalabels' => [
                'display' => false,
            ],
        ],
        "title" => [
            "display" => false,
        ],
        'scales' => [
            'xAxes' => [
                [
                    'ticks' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
            'yAxes' => [
                [
                    'id' => 'defaultYID',
                    'ticks' => [
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ],
    ],
    'defaults' => [
        'global' => [
            'defaultFontSize' => 10,
            'defaultFontFamily' => '"Open Sans","Helvetica Neue","Helvetica","Arial","sans-serif"',
            'elements' => [
                'line' => [
                    'fill' => false,
                    'lineTension' => 0.9,
                ]
            ],
            'tooltips' => [
                'cornerRadius' => 0,
                'backgroundColor' => 'rgba(255,255,255,0.9)',
                'borderWidth' => 0.5,
                'borderColor' => 'rgba(0,0,0,0.8)',
                'bodyFontColor' => '#000',
                'titleFontColor' => '#000',
            ],
            'legend' => [
                'labels' => [
                    'boxWidth' => 12,
                ],
            ],
            'maintainAspectRatio' => false,
        ],
    ],
];

$config['arrColors'] = [
    "#8bbc21", "#2f7ed8", "#f28f43", "#1aadce", "#77a1e5", "#0d233a", "#c42525", "#a6c96a", "#910000",
    '#0048Ba', '#B0BF1A', '#C46210', '#FFBF00', '#9966CC', '#841B2D', '#FAEBD7', '#8DB600', '#D0FF14',
    '#FF9966', '#007FFF', '#FF91AF', '#E94196', '#CAE00D', '#54626F'
];