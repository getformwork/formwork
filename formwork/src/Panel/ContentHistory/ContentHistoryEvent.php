<?php

namespace Formwork\Panel\ContentHistory;

enum ContentHistoryEvent: string
{
    case Created = 'created';
    case Edited = 'edited';
}
