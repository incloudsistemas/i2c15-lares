<?php

/**
 * Retrieves the corresponding social media link for a given platform.
 *
 */

if (!function_exists('GetSocialMedia')) {
    function GetSocialMedia(array $social): ?string
    {
        $socialIcons = [
            'instagram' => ['bg-instagram', 'fa-brands fa-instagram'],
            'facebook'  => ['bg-facebook', 'fa-brands fa-facebook-f'],
            'tikTok'    => ['bg-tiktok', 'fa-brands fa-tiktok'],
            'youtube'   => ['bg-youtube', 'fa-brands fa-youtube'],
            'twitter'   => ['bg-x-twitter', 'fa-brands fa-x-twitter'],
            'linkedin'  => ['bg-linkedin', 'fa-brands fa-linkedin'],
            'google'    => ['bg-google', 'fa-brands fa-google'],
        ];

        if (array_key_exists($social['role'], $socialIcons)) {
            $icon = $socialIcons[$social['role']];
            return "
                <a href='{$social['url']}' target='_blank' class='social-icon si-small text-white {$icon[0]}'>
                    <i class='{$icon[1]}'></i>
                    <i class='{$icon[1]}'></i>
                </a>
            ";
        }

        return null;
    }
}
