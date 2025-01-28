<?php

enum AuthTokenLevel : int
{
    case NORMAL = 1; // a normal auth token that just has permission to access what it is directly assigned.
    case CERTIFICATE_CREATOR = 2; // auth token that has the ability to read/create/update certificates they are assigned to.
    case FULL_READ = 3; // auth token that has the ability to read any certificate, but not create/update anything
    case ADMIN = 4; // auth token that can do anything.
}
