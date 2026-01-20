<?php

namespace App\Enums;

enum SourceType: string
{
    case GitHubRepo = 'github_repo';
    case RssFeed = 'rss_feed';
    case YouTubeChannel = 'youtube_channel';
}
