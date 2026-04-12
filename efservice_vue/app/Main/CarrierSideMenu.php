<?php

namespace App\Main;
use Illuminate\Support\Facades\Auth;
use App\Models\DriverArchive;

class CarrierSideMenu
{
    public static function menu()
    {
        // Verificar si hay un usuario autenticado y tiene carrierDetails
        $carrier = null;
        $inactiveDriversCount = 0;
        
        if (Auth::check() && Auth::user()->carrierDetails) {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            if ($carrier) {
                $inactiveDriversCount = DriverArchive::where('carrier_id', $carrier->id)
                    ->where('status', 'archived')
                    ->count();
            }
        }

        // Build inactive drivers menu item with optional badge
        $inactiveDriversMenuItem = [
            'icon' => "archive",
            'route_name' => "carrier.drivers.inactive.index",
            'params' => [],
            'title' => "Inactive Drivers",
        ];
        
        // Add badge only if count > 0
        if ($inactiveDriversCount > 0) {
            $inactiveDriversMenuItem['badge'] = $inactiveDriversCount;
        }

        return [
            "DASHBOARD",
            [
                'icon' => "BookMarked",
                'route_name' => "carrier.dashboard",
                'params' => [],
                'title' => "Dashboard",
            ],            
            "DRIVERS",
            [
                'icon' => "users",
                'route_name' => "#",
                'params' => [],
                'title' => "Drivers Management",
                'sub_menu' => [
                    [
                        'icon' => "user",
                        'route_name' => "carrier.drivers.index",
                        'params' => [],
                        'title' => "List Drivers",
                    ],
                    [
                        'icon' => "CreditCard",
                        'route_name' => "carrier.licenses.index",
                        'params' => [],
                        'title' => "Licenses",
                    ],
                    [
                        'icon' => "FileHeart",
                        'route_name' => "carrier.medical-records.index",
                        'params' => [],
                        'title' => "Medical Records",
                    ],
                    [
                        'icon' => "GraduationCap",
                        'route_name' => "carrier.training-schools.index",
                        'params' => [],
                        'title' => "Training Schools",
                    ],
                    [
                        'icon' => "BookOpen",
                        'route_name' => "carrier.courses.index",
                        'params' => [],
                        'title' => "Courses",
                    ],
                    [
                        'icon' => "GraduationCap",
                        'route_name' => "carrier.trainings.index",
                        'params' => [],
                        'title' => "Trainings",
                    ],
                    [
                        'icon' => "alertTriangle",
                        'route_name' => "carrier.drivers.accidents.index",
                        'params' => [],
                        'title' => "Accidents",
                    ],
                    [
                        'icon' => "alertTriangle",
                        'route_name' => "carrier.drivers.traffic.index",
                        'params' => [],
                        'title' => "Traffic Convictions",
                    ],
                    [
                        'icon' => "clipboardCheck",
                        'route_name' => "carrier.drivers.testings.index",
                        'params' => [],
                        'title' => "Drug Tests",
                    ],
                    [
                        'icon' => "clipboardList",
                        'route_name' => "carrier.drivers.inspections.index",
                        'params' => [],
                        'title' => "Inspections",
                    ],
                    $inactiveDriversMenuItem,
                ],
            ],
            "VEHICLES",
            [
                'icon' => "truck",
                'title' => "Vehicles Management",
                'sub_menu' => [
                    [
                        'icon' => "list",
                        'route_name' => "carrier.vehicles.index",
                        'params' => [],
                        'title' => "List Vehicles",
                    ],
                    [
                        'icon' => "FileText",
                        'route_name' => "carrier.vehicles-documents.index",
                        'params' => [],
                        'title' => "Documents Overview",
                    ],
                    [
                        'icon' => "Truck",
                        'route_name' => "carrier.driver-vehicle-management.index",
                        'params' => [],
                        'title' => "Driver & Vehicle Management",
                    ],
                    [
                        'icon' => "settings",
                        'route_name' => "carrier.maintenance.index",
                        'params' => [],
                        'title' => "Maintenance",
                    ],
                    [
                        'icon' => "wrench",
                        'route_name' => "carrier.emergency-repairs.index",
                        'params' => [],
                        'title' => "Repairs Management",
                    ],
                    [
                        'icon' => "tag",
                        'route_name' => "carrier.vehicle-makes.index",
                        'params' => [],
                        'title' => "Vehicle Makes",
                    ],
                    [
                        'icon' => "layers",
                        'route_name' => "carrier.vehicle-types.index",
                        'params' => [],
                        'title' => "Vehicle Types",
                    ],
                ],
            ],
            "MESSAGES",
            [
                'icon' => "Mail",
                'route_name' => "#",
                'params' => [],
                'title' => "Messages",
                'sub_menu' => [
                    [
                        'icon' => "Inbox",
                        'route_name' => "carrier.messages.index",
                        'params' => [],
                        'title' => "All Messages",
                    ],
                    [
                        'icon' => "BarChart3",
                        'route_name' => "carrier.messages.dashboard",
                        'params' => [],
                        'title' => "Dashboard",
                    ],
                    [
                        'icon' => "PenSquare",
                        'route_name' => "carrier.messages.create",
                        'params' => [],
                        'title' => "New Message",
                    ],
                ],
            ],
            "TRIPS",
            [
                'icon' => "MapPin",
                'route_name' => "#",
                'params' => [],
                'title' => "Trip Management",
                'sub_menu' => [
                    [
                        'icon' => "LayoutDashboard",
                        'route_name' => "carrier.trips.dashboard",
                        'params' => [],
                        'title' => "Dashboard",
                    ],
                    [
                        'icon' => "List",
                        'route_name' => "carrier.trips.index",
                        'params' => [],
                        'title' => "All Trips",
                    ],
                    [
                        'icon' => "Plus",
                        'route_name' => "carrier.trips.create",
                        'params' => [],
                        'title' => "Create Trip",
                    ],
                ],
            ],
            "HOURS OF SERVICE",
            [
                'icon' => "Clock",
                'route_name' => "#",
                'params' => [],
                'title' => "HOS Management",
                'sub_menu' => [
                    [
                        'icon' => "LayoutDashboard",
                        'route_name' => "carrier.hos.dashboard",
                        'params' => [],
                        'title' => "HOS Dashboard",
                    ],
                    [
                        'icon' => "Settings",
                        'route_name' => "carrier.hos.fmcsa.configuration",
                        'params' => [],
                        'title' => "FMCSA Configuration",
                    ],
                    [
                        'icon' => "Users",
                        'route_name' => "carrier.drivers.hos.index",
                        'params' => [],
                        'title' => "Driver HOS Settings",
                    ],
                    [
                        'icon' => "AlertTriangle",
                        'route_name' => "carrier.violations.index",
                        'params' => [],
                        'title' => "Violations",
                    ],
                ],
            ],

            "REPORTS",            
            [
                'icon' => "FileText",
                'route_name' => "carrier.reports.index",
                'params' => [],
                'title' => "Dashboard Reports",
            ],
            [
                'icon' => "user",
                'route_name' => "carrier.reports.drivers",
                'params' => [],
                'title' => "Driver Reports",
            ],

            [
                'icon' => "Truck",
                'route_name' => "carrier.reports.vehicles",
                'params' => [],
                'title' => "Vehicles Reports",
            ],
            
            [
                'icon' => "FileText",
                'route_name' => "carrier.reports.accidents",
                'params' => [],
                'title' => "Accident Reports",
            ],
            [
                'icon' => "FileHeart",
                'route_name' => "carrier.reports.medical-records",
                'params' => [],
                'title' => "Medical Reports",
            ],
            
            [
                'icon' => "CreditCard",
                'route_name' => "carrier.reports.licenses",
                'params' => [],
                'title' => "License Reports",
            ],
            [
                'icon' => "settings",
                'route_name' => "carrier.reports.maintenance",
                'params' => [],
                'title' => "Maintenance Reports",
            ],
            
            [
                'icon' => "wrench",
                'route_name' => "carrier.reports.repairs",
                'params' => [],
                'title' => "Repairs Reports",
            ],
            [
                'icon' => "CalendarDays",
                'route_name' => "carrier.reports.monthly",
                'params' => [],
                'title' => "Monthly Summary",
            ],
            [
                'icon' => "MapPin",
                'route_name' => "carrier.reports.trips",
                'params' => [],
                'title' => "Trip Reports",
            ],
            [
                'icon' => "Clock",
                'route_name' => "carrier.reports.hos",
                'params' => [],
                'title' => "HOS Reports",
            ],
            [
                'icon' => "AlertOctagon",
                'route_name' => "carrier.reports.violations",
                'params' => [],
                'title' => "Violations Reports",
            ],            
            "CARRIER PROFILE",
            [
                'icon' => "Bell",
                'route_name' => "carrier.notifications.index",
                'params' => [],
                'title' => "Notifications",
            ],
            [
                'icon' => "user", // o "userCircle" si prefieres
                'route_name' => "carrier.profile",
                'params' => [],
                'title' => "My Profile",
            ],
            // ... otros elementos del menú
        ];
    }
}
