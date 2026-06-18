<?php

namespace Database\Factories\Concerns;

use Faker\Factory;
use Faker\Generator;

trait UsesPersianFaker
{
    protected function persianFaker(): Generator
    {
        return Factory::create('fa_IR');
    }

    protected function persianMobile(): string
    {
        return '09'.fake()->numerify('#########');
    }

    protected function nationalId(): string
    {
        return fake()->unique()->numerify('##########');
    }
}
