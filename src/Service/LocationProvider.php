<?php

// src/Service/UKLocationProvider.php
namespace App\Service;

class LocationProvider
{
    private array $locations = [
        'Greater London' => [
            'London', 'Westminster', 'Camden', 'Greenwich', 'Hackney', 'Croydon',
            'Bromley', 'Barnet', 'Enfield', 'Havering'
        ],
        'Greater Manchester' => [
            'Manchester', 'Salford', 'Bolton', 'Bury', 'Oldham', 'Rochdale',
            'Stockport', 'Tameside', 'Trafford', 'Wigan'
        ],
        'West Midlands' => [
            'Birmingham', 'Coventry', 'Wolverhampton', 'Solihull', 'Walsall',
            'Dudley', 'Sandwell', 'Sutton Coldfield', 'West Bromwich'
        ],
        'West Yorkshire' => [
            'Leeds', 'Bradford', 'Wakefield', 'Huddersfield', 'Halifax',
            'Dewsbury', 'Keighley', 'Batley', 'Pudsey'
        ],
        'Merseyside' => [
            'Liverpool', 'Birkenhead', 'Southport', 'St Helens', 'Bootle',
            'Wallasey', 'Kirkby', 'Crosby', 'Formby'
        ],
        'South Yorkshire' => [
            'Sheffield', 'Doncaster', 'Rotherham', 'Barnsley', 'Bentley',
            'Wath upon Dearne', 'Wombwell', 'Hoyland', 'Conisbrough'
        ],
        'East Sussex' => [
            'Brighton', 'Hove', 'Eastbourne', 'Hastings', 'Lewes',
            'Peacehaven', 'Seaford', 'Uckfield', 'Crowborough'
        ],
        'Kent' => [
            'Canterbury', 'Maidstone', 'Dover', 'Ashford', 'Folkestone',
            'Margate', 'Dartford', 'Rochester', 'Gillingham', 'Chatham'
        ],
        'Essex' => [
            'Chelmsford', 'Southend-on-Sea', 'Colchester', 'Basildon', 'Harlow',
            'Brentwood', 'Braintree', 'Clacton-on-Sea', 'Grays', 'Rayleigh'
        ],
        'Hampshire' => [
            'Southampton', 'Portsmouth', 'Winchester', 'Basingstoke', 'Eastleigh',
            'Fareham', 'Gosport', 'Andover', 'Havant', 'Fleet'
        ],
        'Surrey' => [
            'Guildford', 'Woking', 'Croydon', 'Sutton', 'Kingston upon Thames',
            'Epsom', 'Redhill', 'Camberley', 'Staines', 'Farnham'
        ],
        'Devon' => [
            'Exeter', 'Plymouth', 'Torquay', 'Paignton', 'Barnstaple',
            'Newton Abbot', 'Tiverton', 'Exmouth', 'Bideford', 'Tavistock'
        ],
        'Norfolk' => [
            'Norwich', "King's Lynn", 'Great Yarmouth', 'Thetford', 'Dereham',
            'North Walsham', 'Wymondham', 'Attleborough', 'Downham Market'
        ],
        'Oxfordshire' => [
            'Oxford', 'Banbury', 'Abingdon', 'Bicester', 'Witney',
            'Didcot', 'Carterton', 'Henley-on-Thames', 'Thame', 'Wantage'
        ],
        'Cambridgeshire' => [
            'Cambridge', 'Peterborough', 'Ely', 'Huntingdon', 'St Neots',
            'Wisbech', 'March', 'St Ives', 'Whittlesey', 'Chatteris'
        ]
    ];

    public function getCounties(): array
    {
        return array_keys($this->locations);
    }

    public function getCitiesByCounty(string $county): array
    {
        return $this->locations[$county] ?? [];
    }

    public function getAllLocations(): array
    {
        return $this->locations;
    }
}