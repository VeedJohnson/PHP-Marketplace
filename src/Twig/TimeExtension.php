<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('smart_date', [$this, 'formatSmartDate']),
        ];
    }

    public function formatSmartDate(\DateTimeInterface $date): string
    {
        $now = new \DateTime();
        $diff = $now->diff($date);
        $diffDays = (int) $diff->format('%a');

        if ($diffDays === 0) {
            if ($diff->h === 0) {
                return $diff->i === 1 ? '1 minute ago' : $diff->i . ' minutes ago';
            }
            return $diff->h === 1 ? '1 hour ago' : $diff->h . ' hours ago';
        }

        if ($diffDays === 1) {
            return 'Yesterday';
        }

        if ($diffDays <= 7) {
            return $diffDays . ' days ago';
        }

        return $date->format('F j, Y');
    }
}