<?php
use App\db\Tools\DBQuery;

        DBQuery::raw("SET FOREIGN_KEY_CHECKS = 0;");
        DBQuery::raw("TRUNCATE table categories;");
        DBQuery::raw("TRUNCATE table images;");
        DBQuery::raw("TRUNCATE table items;");
        DBQuery::raw("TRUNCATE table config;");
        DBQuery::insert('categories', [
            'name' => 'Undefined',
        ]);
        DBQuery::raw("SET FOREIGN_KEY_CHECKS = 1;");

        file_put_contents(LOGS . '/pageCounter.txt', '');
        file_put_contents(LOGS . '/parser.log', '');
        file_put_contents(LOGS . '/stream_bash_log.log', '');