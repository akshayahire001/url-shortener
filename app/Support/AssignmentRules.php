<?php

namespace App\Support;

class AssignmentRules
{
    public const SINGLE_COMPANY = "Your service mustn't have multiple companies.";

    public const USER_ROLE = 'Each user will neither be an Admin nor a Member.';

    public const SUPER_ADMIN_INVITE = "SuperAdmin can't invite an Admin in a new company.";

    public const ADMIN_INVITE = "An Admin can't invite another Admin or Member in their own company.";

    public const ADMIN_MEMBER_CREATE = "Admin and Member can't create short urls.";

    public const SUPER_ADMIN_CREATE = 'SuperAdmin cannot create short urls.';

    public const SUPER_ADMIN_LIST = "SuperAdmin can't see the list of all short urls for every company.";

    public const ADMIN_LIST = 'Admin can only see the list of all short urls not created in their own company.';

    public const MEMBER_LIST = 'Member can only see the list of all short urls not created by themselves.';

    public const NOT_PUBLIC = 'Short urls are not publicly resolvable and do not redirect to the original url.';
}
