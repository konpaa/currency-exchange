<?php

return [
    'validation' => [
        'amount_required' => 'The amount to convert is required.',
        'from_required' => 'The source currency is required.',
        'to_required' => 'The target currency is required.',
        'amount_numeric' => 'The amount must be a number.',
        'amount_min' => 'The amount must be at least zero.',
        'from_in' => 'The source currency must be from the available list.',
        'to_in' => 'The target currency must be from the available list.',
    ],
    'errors' => [
        'rate_not_found' => 'Rate for currency :currency not found.',
        'amount_invalid' => 'The amount must be a non-negative number.',
    ],
];
