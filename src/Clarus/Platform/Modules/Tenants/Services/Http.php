<?php

namespace Clarus\Platform\Modules\Tenants\Services;

use Clarus\Platform\Modules\Tenants\Http\Memberships\Create as CreateMembership;
use Clarus\Platform\Modules\Tenants\Http\Memberships\Delete as DeleteMembership;
use Clarus\Platform\Modules\Tenants\Http\Memberships\Update as UpdateMembership;
use Clarus\Platform\Modules\Tenants\Http\Memberships\XList as ListMemberships;
use Clarus\Platform\Modules\Tenants\Http\Tenants\Create as CreateTenant;
use Clarus\Platform\Modules\Tenants\Http\Tenants\GetCurrent;
use Clarus\Platform\Modules\Tenants\Http\Tenants\XList as ListTenants;
use Utopia\Platform\Service;

class Http extends Service
{
    public function __construct()
    {
        $this->type = Service::TYPE_HTTP;

        $this->addAction(CreateTenant::getName(), new CreateTenant());
        $this->addAction(ListTenants::getName(), new ListTenants());
        $this->addAction(GetCurrent::getName(), new GetCurrent());
        $this->addAction(CreateMembership::getName(), new CreateMembership());
        $this->addAction(ListMemberships::getName(), new ListMemberships());
        $this->addAction(UpdateMembership::getName(), new UpdateMembership());
        $this->addAction(DeleteMembership::getName(), new DeleteMembership());
    }
}
