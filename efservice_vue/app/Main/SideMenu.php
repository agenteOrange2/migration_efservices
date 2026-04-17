<?php

namespace App\Main;

class SideMenu
{
    /**
     * List of side menu items.
     */
    public static function menu(): array
    {
        return [
            "DASHBOARD",
            [
                'icon' => "BookMarked",
                'route_name' => "admin.dashboard",
                'params' => [],
                'title' => "Dashboard",
            ],
            "CARRIERS MANAGEMENT",
            [
                'icon' => "users",
                'route_name' => "#",
                'params' => [],
                'title' => "Transporters",
                'sub_menu' => [
                    [
                        'icon' => "user-plus",
                        'route_name' => "admin.carriers.index",
                        'params' => [],
                        'title' => "Carriers",
                    ],
                    // [
                    //     'icon' => "user-check",
                    //     'route_name' => "admin.product-grid",
                    //     'params' => [],
                    //     'title' => "Permisos",
                    // ],
                    [
                        'icon' => "vote",
                        'route_name' => "admin.carriers-documents.index",
                        'params' => [],
                        'title' => "All Documents",
                    ],
                    [
                        'icon' => "UserSquare",
                        'route_name' => "admin.document-types.index",
                        'params' => [],
                        'title' => "Document Type",
                    ],
                    [
                        'icon' => "FileText",
                        'route_name' => "admin.document-types.default-policy",
                        'params' => [],
                        'title' => "Default Policy",
                    ],
                ],
            ],
            "DRIVERS MANAGEMENT",
            [
                'icon' => "car-front",
                'route_name' => "#",
                'params' => [],
                'title' => "Drivers",
                'sub_menu' => [
                    [
                        'icon' => "user-check",
                        'route_name' => "admin.driver-recruitment.index",
                        'params' => [],
                        'title' => "Recruitment",
                    ],
                    [
                        'icon' => "user-plus",
                        'route_name' => "admin.drivers.index",
                        'params' => [],
                        'title' => "Drivers",
                    ],
                    [
                        'icon' => "user-plus",
                        'route_name' => "admin.licenses.index",
                        'params' => [],
                        'title' => "Drivers Licenses",
                    ],
                    [
                        'icon' => "heart-pulse",
                        'route_name' => "admin.medical-records.index",
                        'params' => [],
                        'title' => "Medical Records",
                    ],
                    [
                        'icon' => "file-warning",
                        'route_name' => "admin.drivers.employment-verification.index",
                        'params' => [],
                        'title' => "Employment Verification",
                    ],
                    [
                        'icon' => "building",
                        'route_name' => "admin.companies.index",
                        'params' => [],
                        'title' => "Companies",
                    ],
                    [
                        'icon' => "file-warning",
                        'route_name' => "admin.accidents.index",
                        'params' => [],
                        'title' => "Accidents",
                    ],
                    [
                        'icon' => "alert-triangle",
                        'route_name' => "admin.traffic.index",
                        'params' => [],
                        'title' => "Traffic Convictions",
                    ],
                    [
                        'icon' => "badge-info",
                        'route_name' => "admin.driver-testings.index",
                        'params' => [],
                        'title' => "Testing",
                    ],
                    [
                        'icon' => "view",
                        'route_name' => "admin.inspections.index",
                        'params' => [],
                        'title' => "Inspections",
                    ],
                    [
                        'icon' => "school",
                        'route_name' => "admin.training-schools.index",
                        'params' => [],
                        'title' => "Driving Schools",
                    ],
                    [
                        'icon' => "graduation-cap",
                        'route_name' => "admin.trainings.index",
                        'params' => [],
                        'title' => "Trainings",
                    ],
                    [
                        'icon' => "clipboard-list",
                        'route_name' => "admin.training-assignments.index",
                        'params' => [],
                        'title' => "Training Assignments",
                    ],
                    [
                        'icon' => "shield-check",
                        'route_name' => "admin.courses.index",
                        'params' => [],
                        'title' => "Courses",
                    ],
                    [
                        'icon' => "archive",
                        'route_name' => "admin.drivers.archived.index",
                        'params' => [],
                        'title' => "Archived Drivers",
                    ],
                ],
            ],
            "VEHICLES MANAGEMENT",
            [
                'icon' => "bus",
                'route_name' => "#",
                'params' => [],
                'title' => "Vehicles",
                'sub_menu' => [
                    [
                        'icon' => "layout-dashboard",
                        'route_name' => "admin.vehicles.dashboard",
                        'params' => [],
                        'title' => "Dashboard",
                    ],
                    [
                        'icon' => "car-front",
                        'route_name' => "admin.vehicles.index",
                        'params' => [],
                        'title' => "Vehicle Profile",
                    ],
                    [
                        'icon' => "car-front",
                        'route_name' => "admin.vehicle-makes.index",
                        'params' => [],
                        'title' => "Vehicle Make",
                    ],
                    [
                        'icon' => "car-front",
                        'route_name' => "admin.vehicle-types.index",
                        'params' => [],
                        'title' => "Vehicle Type",
                    ],
                    [
                        'icon' => "users",
                        'route_name' => "admin.driver-types.index",
                        'params' => [],
                        'title' => "Driver Types",
                    ],

                    [
                        'icon' => "file-text",
                        'route_name' => "admin.vehicles-documents.index",
                        'params' => [],
                        'title' => "Documents Overview",
                    ],
                    [
                        'icon' => "wrench",
                        'route_name' => "admin.maintenance.index",
                        'params' => [],
                        'title' => "Maintenance",
                    ],
                    [
                        'icon' => "alert-circle",
                        'route_name' => "admin.vehicles.emergency-repairs.index",
                        'params' => [],
                        'title' => "Repairs",
                    ],
                ],
            ],
            "REPORT GENERATOR",
            [
                'icon' => "file-text",
                'route_name' => "#",
                'params' => [],
                'title' => "Reports",
                'sub_menu' => [
                    [
                        'icon' => "layout-dashboard",
                        'route_name' => "admin.reports.index",
                        'params' => [],
                        'title' => "All Reports",
                    ],
                    [
                        'icon' => "user-check",
                        'route_name' => "admin.reports.active-drivers",
                        'params' => [],
                        'title' => "Active Drivers List",
                    ],
                    [
                        'icon' => "user-minus",
                        'route_name' => "admin.reports.inactive-drivers",
                        'params' => [],
                        'title' => "Inactive Drivers List",
                    ],
                    [
                        'icon' => "user-check",
                        'route_name' => "admin.reports.driver-prospects",
                        'params' => [],
                        'title' => "Prospect Drivers List",
                    ],
                    [
                        'icon' => "truck",
                        'route_name' => "admin.reports.equipment-list",
                        'params' => [],
                        'title' => "Equipment List",
                    ],
                    [
                        'icon' => "file-archive",
                        'route_name' => "admin.reports.carrier-documents",
                        'params' => [],
                        'title' => "Carrier Documents",
                    ],
                    [
                        'icon' => "alert-triangle",
                        'route_name' => "admin.reports.accidents",
                        'params' => [],
                        'title' => "Accidents Manager",
                    ],
                    [
                        'icon' => "wrench",
                        'route_name' => "admin.reports.maintenances",
                        'params' => [],
                        'title' => "Maintenances Report",
                    ],
                    [
                        'icon' => "alert-circle",
                        'route_name' => "admin.reports.emergency-repairs",
                        'params' => [],
                        'title' => "Emergency Repairs Report",
                    ],
                    [
                        'icon' => "graduation-cap",
                        'route_name' => "admin.reports.trainings",
                        'params' => [],
                        'title' => "Trainings Report",
                    ],
                    [
                        'icon' => "arrow-right-left",
                        'route_name' => "admin.reports.migrations",
                        'params' => [],
                        'title' => "Migrations Report",
                    ],
                    [
                        'icon' => "map-pin",
                        'route_name' => "admin.reports.trips",
                        'params' => [],
                        'title' => "Trip Report",
                    ],
                    [
                        'icon' => "clock",
                        'route_name' => "admin.reports.hos",
                        'params' => [],
                        'title' => "HOS Report",
                    ],
                    [
                        'icon' => "alert-octagon",
                        'route_name' => "admin.reports.violations",
                        'params' => [],
                        'title' => "Violations Report",
                    ],
                ],
            ],

            "TRIPS",
            [
                'icon' => "map-pin",
                'route_name' => "#",
                'params' => [],
                'title' => "Trip Management",
                'sub_menu' => [
                    [
                        'icon' => "list",
                        'route_name' => "admin.trips.index",
                        'params' => [],
                        'title' => "All Trips",
                    ],
                    [
                        'icon' => "bar-chart",
                        'route_name' => "admin.trips.statistics",
                        'params' => [],
                        'title' => "Statistics",
                    ],
                ],
            ],

            "HOURS OF SERVICE",
            [
                'icon' => "clock",
                'route_name' => "#",
                'params' => [],
                'title' => "HOS Management",
                'sub_menu' => [
                    [
                        'icon' => "layout-dashboard",
                        'route_name' => "admin.hos.dashboard",
                        'params' => [],
                        'title' => "HOS Overview",
                    ],
                    [
                        'icon' => "users",
                        'route_name' => "admin.drivers.hos.index",
                        'params' => [],
                        'title' => "Driver HOS Settings",
                    ],
                    [
                        'icon' => "alert-triangle",
                        'route_name' => "admin.hos.violations",
                        'params' => [],
                        'title' => "All Violations",
                    ],
                    [
                        'icon' => "file-text",
                        'route_name' => "admin.hos.documents.index",
                        'params' => [],
                        'title' => "HOS Documents",
                    ],
                ],
            ],

            "DATA MANAGEMENT",
            [
                'icon' => "upload",
                'route_name' => "admin.imports.index",
                'params' => [],
                'title' => "Bulk Import",
            ],

            "MEMBERSHIPS",
            [
                'icon' => "package-search",
                'route_name' => "admin.memberships.index",
                'params' => [],
                'title' => "Memberships",
            ],

            "MESSAGES MANAGEMENT",
            [
                'icon' => "mail",
                'route_name' => "#",
                'params' => [],
                'title' => "Messages",
                'sub_menu' => [
                    [
                        'icon' => "layout-dashboard",
                        'route_name' => "admin.messages.dashboard",
                        'params' => [],
                        'title' => "Dashboard",
                    ],
                    [
                        'icon' => "list",
                        'route_name' => "admin.messages.index",
                        'params' => [],
                        'title' => "All Messages",
                    ],
                    [
                        'icon' => "plus",
                        'route_name' => "admin.messages.create",
                        'params' => [],
                        'title' => "New Message",
                    ],
                    [
                        'icon' => "Bell",
                        'route_name' => "admin.notifications.index",
                        'params' => [],
                        'title' => "Notifications",
                    ],
                ],
            ],
            "CONTACT MANAGEMENT",
            [
                'icon' => "mail",
                'route_name' => "#",
                'params' => [],
                'title' => "Messages Contact",
                'sub_menu' => [
                    [
                        'icon' => "MessageSquare",
                        'route_name' => "admin.contact-submissions.index",
                        'params' => [],
                        'title' => "Contact Submissions",
                    ],
                    [
                        'icon' => "CreditCard",
                        'route_name' => "admin.plan-requests.index",
                        'params' => [],
                        'title' => "Plan Requests",
                    ],

                ],
            ],

            "USER MANAGEMENT",
            [
                'icon' => "UserSquare",
                'route_name' => "#",
                'params' => [],
                'title' => "Users",
                'sub_menu' => [

                    [
                        'icon' => "UserSquare",
                        'route_name' => "admin.users.index",
                        'params' => [],
                        'title' => "Users",
                    ],
                    [
                        'icon' => "shield-ban",
                        'route_name' => "admin.permissions.index",
                        'params' => [],
                        'title' => "Permissions",
                    ],
                    [
                        'icon' => "users",
                        'route_name' => "admin.roles.index",
                        'params' => [],
                        'title' => "Roles",
                    ],
                ],
            ],

            /*
            "PERSONAL DASHBOARD",
            [
                'icon' => "Presentation",
                'route_name' => "admin.profile-overview",
                'params' => [],
                'title' => "Profile Overview",
            ],
            [
                'icon' => "CalendarRange",
                'route_name' => "admin.profile-overview-events",
                'params' => [],
                'title' => "Events",
            ],
            [
                'icon' => "Medal",
                'route_name' => "admin.profile-overview-achievements",
                'params' => [],
                'title' => "Achievements",
            ],
            [
                'icon' => "TabletSmartphone",
                'route_name' => "admin.profile-overview-contacts",
                'params' => [],
                'title' => "Contacts",
            ],
            [
                'icon' => "Snail",
                'route_name' => "admin.profile-overview-default",
                'params' => [],
                'title' => "Default",
            ],
            "GENERAL SETTINGS",
            [
                'icon' => "Briefcase",
                'route_name' => "admin.settings",
                'params' => [],
                'title' => "Profile Info",
            ],
            [
                'icon' => "MailCheck",
                'route_name' => "admin.settings-email-settings",
                'params' => [],
                'title' => "Email Settings",
            ],
            [
                'icon' => "Fingerprint",
                'route_name' => "admin.settings-security",
                'params' => [],
                'title' => "Security",
            ],
            [
                'icon' => "Radar",
                'route_name' => "admin.settings-preferences",
                'params' => [],
                'title' => "Preferences",
            ],
            [
                'icon' => "DoorOpen",
                'route_name' => "admin.settings-two-factor-authentication",
                'params' => [],
                'title' => "Two-factor Authentication",
            ],
            [
                'icon' => "Keyboard",
                'route_name' => "admin.settings-device-history",
                'params' => [],
                'title' => "Device History",
            ],
            [
                'icon' => "Ticket",
                'route_name' => "admin.settings-notification-settings",
                'params' => [],
                'title' => "Notification Settings",
            ],
            [
                'icon' => "BusFront",
                'route_name' => "admin.settings-connected-services",
                'params' => [],
                'title' => "Connected Services",
            ],
            [
                'icon' => "Podcast",
                'route_name' => "admin.settings-social-media-links",
                'params' => [],
                'title' => "Social Media Links",
            ],
            [
                'icon' => "PackageX",
                'route_name' => "admin.settings-account-deactivation",
                'params' => [],
                'title' => "Account Deactivation",
            ],
            "ACCOUNT",
            [
                'icon' => "PercentSquare",
                'route_name' => "admin.billing",
                'params' => [],
                'title' => "Billing",
            ],
            [
                'icon' => "DatabaseZap",
                'route_name' => "admin.invoice",
                'params' => [],
                'title' => "Invoice",
            ],
            "E-COMMERCE",
            [
                'icon' => "BookMarked",
                'route_name' => "admin.categories",
                'params' => [],
                'title' => "Categories",
            ],
            [
                'icon' => "Compass",
                'route_name' => "admin.add-product",
                'params' => [],
                'title' => "Add Product",
            ],
            [
                'icon' => "Table2",
                'route_name' => "admin.products",
                'params' => [],
                'title' => "Products",
                'sub_menu' => [
                    [
                        'icon' => "LayoutPanelTop",
                        'route_name' => "admin.product-list",
                        'params' => [],
                        'title' => "Product List",
                    ],
                    [
                        'icon' => "LayoutPanelLeft",
                        'route_name' => "admin.product-grid",
                        'params' => [],
                        'title' => "Product Grid",
                    ],
                ],
            ],
            [
                'icon' => "SigmaSquare",
                'route_name' => "admin.transactions",
                'params' => [],
                'title' => "Transactions",
                'sub_menu' => [
                    [
                        'icon' => "DivideSquare",
                        'route_name' => "admin.transaction-list",
                        'params' => [],
                        'title' => "Transaction List",
                    ],
                    [
                        'icon' => "PlusSquare",
                        'route_name' => "admin.transaction-detail",
                        'params' => [],
                        'title' => "Transaction Detail",
                    ],
                ],
            ],
            [
                'icon' => "FileArchive",
                'route_name' => "admin.sellers",
                'params' => [],
                'title' => "Sellers",
                'sub_menu' => [
                    [
                        'icon' => "FileImage",
                        'route_name' => "admin.seller-list",
                        'params' => [],
                        'title' => "Seller List",
                    ],
                    [
                        'icon' => "FileBox",
                        'route_name' => "admin.seller-detail",
                        'params' => [],
                        'title' => "Seller Detail",
                    ],
                ],
            ],
            [
                'icon' => "Goal",
                'route_name' => "admin.reviews",
                'params' => [],
                'title' => "Reviews",
            ],
            "AUTHENTICATIONS",
            [
                'icon' => "BookKey",
                'route_name' => "admin.login",
                'params' => [],
                'title' => "Login",
            ],
            [
                'icon' => "BookLock",
                'route_name' => "admin.register",
                'params' => [],
                'title' => "Register",
            ],
            */
        ];
    }
}
