<?php

function app_now(): string
{
    return date('Y-m-d H:i:s');
}

function app_format_datetime(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    return substr(str_replace('T', ' ', $value), 0, 19);
}
