<?php

return [
    'Compression' => [
        'description' => [
            1 => 'Uncompressed',
            2 => 'JPEG compression',
        ],
    ],
    'PhotometricInterpretation' => [
        'description' => [
            2 => 'RGB',
            6 => 'YCbCr',
        ],
    ],
    'Orientation' => [
        'description' => [
            1 => 'Normal',
            2 => 'Flipped horizontally',
            3 => 'Rotated 180 degrees',
            4 => 'Rotated 180 degrees and flipped horizontally',
            5 => 'Rotated 90 degrees, clockwise and flipped horizontally',
            6 => 'Rotated 90 degrees, clockwise',
            7 => 'Rotated 90 degrees, counterclockise and flipped horizontally',
            8 => 'Rotated 90 degrees, counterclockise',
        ],
    ],
    'PlanarConfiguration' => [
        'description' => [
            1 => 'Chunky format',
            2 => 'Planar format',
        ],
    ],
    'YCbCrSubSampling' => [
        'description' => [
            1 => 'YCbCr4:2:2',
            2 => 'YCbCr4:2:0',
        ],
    ],
    'YCbCrPositioning' => [
        'description' => [
            1 => 'Centered',
            2 => 'Co-sited',
        ],
    ],
    'XResolution' => [
        'type' => 'rational',
    ],
    'YResolution' => [
        'type' => 'rational',
    ],
    'ResolutionUnit' => [
        'description' => [
            1 => 'None',
            2 => 'Inches',
            3 => 'Centimeters',
        ],
    ],
    'WhitePoint' => [
        'type' => 'rational',
    ],
    'PrimaryChromaticities' => [
        'type' => 'rational',
    ],
    'YCbCrCoefficients' => [
        'type' => 'rational',
    ],
    'ReferenceBlackWhite' => [
        'type' => 'rational',
    ],
    'DateTime' => [
        'type'       => 'datetime',
        'timeoffset' => 'OffsetTime',
        'subseconds' => 'SubSecTime',
    ],
    'ExifVersion' => [
        'type' => 'version',
    ],
    'FlashPixVersion' => [
        'type' => 'version',
    ],
    'ColorSpace' => [
        'description' => [
            1      => 'sRGB',
            0xffff => 'Uncalibrated',
        ],
    ],
    'Gamma' => [
        'type' => 'rational',
    ],
    'ComponentsConfiguration' => [
        'type'        => 'binary',
        'description' => fn(string $value) => array_map(fn(string $char) => match ((int) $char) {
            1       => 'Y',
            2       => 'Cb',
            3       => 'Cr',
            4       => 'R',
            5       => 'G',
            6       => 'B',
            default => '-',
        }, str_split($value)),
    ],
    'UserComment' => [
        'type' => 'text',
    ],
    'CompressedBitsPerPixel' => [
        'type' => 'rational',
    ],
    'DateTimeOriginal' => [
        'type'       => 'datetime',
        'timeoffset' => 'OffsetTimeOriginal',
        'subseconds' => 'SubSecTimeOriginal',
    ],
    'DateTimeDigitized' => [
        'type'       => 'datetime',
        'timeoffset' => 'OffsetTimeDigitized',
        'subseconds' => 'SubSecTimeDigitized',
    ],
    'ExposureTime' => [
        'description' => function (string $value) {
            [$num, $den] = explode('/', $value . '/1');
            return $num > 1 ? round((int) $num / (int) $den, 1) : "{$num}/{$den}";
        },
    ],
    'FNumber' => [
        'type' => 'rational',
    ],
    'ExposureProgram' => [
        'description' => [
            0 => 'Not defined',
            1 => 'Manual',
            2 => 'Normal program',
            3 => 'Aperture priority',
            4 => 'Shutter priority',
            5 => 'Creative program (biased toward depth of field)',
            6 => 'Action program (biased toward fast shutter speed)',
            7 => 'Portrait mode (for closeup photos with the background out of focus)',
            8 => 'Landscape mode (for landscape photos with the background in focus)',
        ],
    ],
    'SensitivityType' => [
        'description' => [
            0 => 'Unknown',
            1 => 'Standard output sensitivity (SOS)',
            2 => 'Recommended exposure index (REI)',
            3 => 'ISO speed',
            4 => 'Standard output sensitivity (SOS) and recommended exposure index (REI)',
            5 => 'Standard output sensitivity (SOS) and ISO speed',
            6 => 'Recommended exposure index (REI) and ISO speed',
            7 => 'Standard output sensitivity (SOS) and recommended exposure index (REI) and ISO speed',
        ],
    ],
    'ShutterSpeedValue' => [
        'type' => 'rational',
    ],
    'ApertureValue' => [
        'type' => 'rational',
    ],
    'BrightnessValue' => [
        'type' => 'rational',
    ],
    'ExposureBiasValue' => [
        'type' => 'rational',
    ],
    'MaxApertureValue' => [
        'type' => 'rational',
    ],
    'SubjectDistance' => [
        'type' => 'rational',
    ],
    'MeteringMode' => [
        'description' => [
            0   => 'Unknown',
            1   => 'Average',
            2   => 'CenterWeightedAverage',
            3   => 'Spot',
            4   => 'MultiSpot',
            5   => 'Pattern',
            6   => 'Partial',
            255 => 'Other',
        ],
    ],
    'LightSource' => [
        'description' => [
            0   => 'Unknown',
            1   => 'Daylight',
            2   => 'Fluorescent',
            3   => 'Tungsten (incandescent light)',
            4   => 'Flash',
            9   => 'Fine weather',
            10  => 'Cloudy weather',
            11  => 'Shade',
            12  => 'Daylight fluorescent (D 5700 - 7100K)',
            13  => 'Day white fluorescent (N 4600 - 5500K)',
            14  => 'Cool white fluorescent (W 3800 - 4500K)',
            15  => 'White fluorescent (WW 3250 - 3800K)',
            16  => 'Warm white fluorescent (L 2600 - 3250K)',
            17  => 'Standard light A',
            18  => 'Standard light B',
            19  => 'Standard light C',
            20  => 'D55',
            21  => 'D65',
            22  => 'D75',
            23  => 'D50',
            24  => 'ISO studio tungsten',
            255 => 'Other light source',
        ],
    ],
    'Flash' => [
        'description' => function ($value) {
            $status = [
                [
                    0b00 => 'Flash did not fire',
                    0b01 => 'Flash fired',
                ],
                [
                    0b00 => 'No strobe return detection function',
                    0b01 => 'Reserved',
                    0b10 => 'Strobe return light not detected',
                    0b11 => 'Strobe return light detected',
                ],
                [
                    0b00 => 'Unknown flash mode',
                    0b01 => 'Compulsory flash firing',
                    0b10 => 'Compulsory flash suppression',
                    0b11 => 'Auto mode',
                ],
                [
                    0b00 => 'Flash function present',
                    0b01 => 'No flash function',
                ],
                [
                    0b00 => 'No red-eye reduction mode',
                    0b01 => 'Red-eye reduction supported',
                ],
            ];

            $bits = [
                ($value & 0b00000001) >> 0,
                ($value & 0b00000110) >> 1,
                ($value & 0b00011000) >> 3,
                ($value & 0b00100000) >> 5,
                ($value & 0b01000000) >> 6,
            ];

            return [
                'FlashFired'      => $status[0][$bits[0]],
                'FlashReturn'     => $status[1][$bits[1]],
                'FlashMode'       => $status[2][$bits[2]],
                'FlashFunction'   => $status[3][$bits[3]],
                'FlashRedEyeMode' => $status[4][$bits[4]],
            ];
        },
    ],
    'FocalLength' => [
        'type' => 'rational',
    ],
    'FlashEnergy' => [
        'type' => 'rational',
    ],
    'FocalPlaneXResolution' => [
        'type' => 'rational',
    ],
    'FocalPlaneYResolution' => [
        'type' => 'rational',
    ],
    'ExposureIndex' => [
        'type' => 'rational',
    ],
    'SensingMethod' => [
        'description' => [
            1 => 'Not defined',
            2 => 'One-chip color area sensor',
            3 => 'Two-chip color area sensor',
            4 => 'Three-chip color area sensor',
            5 => 'Color sequential area sensor',
            7 => 'Trilinear sensor',
            8 => 'Color sequential linear sensor',
        ],
    ],
    'FileSource' => [
        'type'        => 'binary',
        'description' => [
            0 => 'Others',
            1 => 'Scanner of transparent type',
            2 => 'Scanner of reflex type',
            3 => 'DSC',
        ],
    ],
    'SceneType' => [
        'type'        => 'binary',
        'description' => [
            1 => 'A directly photographed image',
        ],
    ],
    'CustomRendered' => [
        'description' => [
            0 => 'Normal process',
            1 => 'Custom process',
        ],
    ],
    'ExposureMode' => [
        'description' => [
            0 => 'Auto exposure',
            1 => 'Manual exposure',
            2 => 'Auto bracket',
        ],
    ],
    'WhiteBalance' => [
        'description' => [
            0 => 'Auto white balance',
            1 => 'Manual white balance',
        ],
    ],
    'DigitalZoomRatio' => [
        'type' => 'rational',
    ],
    'SceneCaptureType' => [
        'description' => [
            0 => 'Standard',
            1 => 'Landscape',
            2 => 'Portrait',
            3 => 'Night scene',
        ],
    ],
    'GainControl' => [
        'description' => [
            0 => 'None',
            1 => 'Low gain up',
            2 => 'High gain up',
            3 => 'Low gain down',
            4 => 'High gain down',
        ],
    ],
    'Contrast' => [
        'description' => [
            0 => 'Normal',
            1 => 'Soft',
            2 => 'Hard',
        ],
    ],
    'Saturation' => [
        'description' => [
            0 => 'Normal',
            1 => 'Low saturation',
            2 => 'High saturation',
        ],
    ],
    'Sharpness' => [
        'description' => [
            0 => 'Normal',
            1 => 'Soft',
            2 => 'Hard',
        ],
    ],
    'SubjectDistanceRange' => [
        'description' => [
            0 => 'Unknown',
            1 => 'Macro',
            2 => 'Close view',
            3 => 'Distant view',
        ],
    ],
    'CompositeImage' => [
        'description' => [
            0 => 'Unknown',
            1 => 'Non-composite image',
            2 => 'General composite image',
            3 => 'Composite image captured when shooting',
        ],
    ],
    'Temperature' => [
        'type' => 'rational',
    ],
    'Humidity' => [
        'type' => 'rational',
    ],
    'Pressure' => [
        'type' => 'rational',
    ],
    'WaterDepth' => [
        'type' => 'rational',
    ],
    'Acceleration' => [
        'type' => 'rational',
    ],
    'CameraElevationAngle' => [
        'type' => 'rational',
    ],
    'LensSpecification' => [
        'type' => 'rational',
    ],
    'GPSVersion' => [
        'type'        => 'binary',
        'description' => fn(string $value) => sprintf('%d.%d.%d.%d', $value[0], $value[1], $value[2], $value[3]),
    ],
    'GPSLatitude' => [
        'type' => 'coords',
        'ref'  => 'GPSLatitudeRef',
    ],
    'GPSLongitude' => [
        'type' => 'coords',
        'ref'  => 'GPSLongitudeRef',
    ],
    'GPSAltitudeRef' => [
        'type'        => 'binary',
        'description' => [
            0 => 'Sea level',
            1 => 'Sea level reference (negative value)',
        ],
    ],
    'GPSAltitude' => [
        'type' => 'rational',
    ],
    'GPSTimeStamp' => [
        'type' => 'rational',
    ],
    'GPSStatus' => [
        'description' => [
            'A' => 'Measurement in progress',
            'V' => 'Measurement interrupted',
        ],
    ],
    'GPSMeasureMode' => [
        'description' => [
            2 => '2-dimensional measurement',
            3 => '3-dimensional measurement',
        ],
    ],
    'GPSDOP' => [
        'type' => 'rational',
    ],
    'GPSSpeedRef' => [
        'description' => [
            'K' => 'Kilometers per hour',
            'M' => 'Miles per hour',
            'N' => 'Knots',
        ],
    ],
    'GPSSpeed' => [
        'type' => 'rational',
    ],
    'GPSTrackRef' => [
        'description' => [
            'T' => 'True direction',
            'M' => 'Magnetic direction',
        ],
    ],
    'GPSTrack' => [
        'type' => 'rational',
    ],
    'GPSImgDirectionRef' => [
        'description' => [
            'T' => 'True direction',
            'M' => 'Magnetic direction',
        ],
    ],
    'GPSImgDirection' => [
        'type' => 'rational',
    ],
    'GPSDestLatitude' => [
        'type' => 'rational',
    ],
    'GPSDestLongitude' => [
        'type' => 'rational',
    ],
    'GPSDestBearingRef' => [
        'description' => [
            'T' => 'True direction',
            'M' => 'Magnetic direction',
        ],
    ],
    'GPSDestBearing' => [
        'type' => 'rational',
    ],
    'GPSDestDistanceRef' => [
        'description' => [
            'K' => 'Kilometers',
            'M' => 'Miles',
            'N' => 'Nautical miles',
        ],
    ],
    'GPSDestDistance' => [
        'type' => 'rational',
    ],
    'GPSProcessingMode' => [
        'type' => 'text',
    ],
    'GPSAreaInformation' => [
        'type' => 'text',
    ],
    'GPSDifferential' => [
        'description' => [
            0 => 'Measurement without differential correction',
            1 => 'Differential correction applied',
        ],
    ],
    'GPSHPositioningError' => [
        'type' => 'rational',
    ],
    'InteroperabilityIndex' => [
        'description' => [
            'R98' => 'File conforming to R98 file specification of Recommended Exif Interoperability Rules (Exif R 98) or to DCF basic file stipulated by Design Rule for Camera File System',
            'THM' => 'File conforming to DCF thumbnail file stipulated by Design rule for Camera File System',
            'R03' => 'File conforming to DCF Option File stipulated by Design rule for Camera File System',
        ],
    ],
];
