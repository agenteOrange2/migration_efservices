<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'
import FlashMessages from '@/components/shared/FlashMessages.vue'
import FormInput from '@/components/Base/Form/FormInput.vue'
import FormLabel from '@/components/Base/Form/FormLabel.vue'
import FormTextarea from '@/components/Base/Form/FormTextarea.vue'
import InputError from '@/components/InputError.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { Building2, CheckCircle2, Menu, Phone, User, X } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue'

type Locale = 'en' | 'es'
type PlatformTabKey = 'general' | 'drivers' | 'testing'

interface LandingPlan {
    id: number | string
    name: string
    description: string | null
    price: number
    pricing_type: string
    max_users: number
    max_drivers: number
    max_vehicles: number
    is_popular: boolean
}

interface LandingStats {
    activeCarriers: number
    registeredDrivers: number
    documentsManaged: number
    complianceRate: number
}

interface ContactInfo {
    phone: string
    email: string
    address: string
    whatsapp_url: string
}

const props = defineProps<{
    canRegister: boolean
    stats: LandingStats
    plans: LandingPlan[]
    contact: ContactInfo
}>()

const { canRegister, stats, plans, contact } = toRefs(props)
const page = usePage()
const authUser = computed(() => (page.props.auth?.user ?? null) as null | { roles?: string[] })

const locale = ref<Locale>('en')
const mobileMenuOpen = ref(false)
const selectedTab = ref<PlatformTabKey>('general')
const planRequestOpen = ref(false)
const selectedPlan = ref<LandingPlan | null>(null)
const headerScrolled = ref(false)

const translations = {
    en: {
        nav: {
            features: 'Features',
            pricing: 'Pricing',
            testimonials: 'Testimonials',
            contact: 'Contact',
            login: 'Login',
            dashboard: 'Dashboard',
            contactBtn: 'Contact',
            registerCarrier: 'Register as Carrier',
            registerDriver: 'Register as Driver',
        },
        hero: {
            title: 'YOUR TRUSTED PARTNER IN <br><span class="text-brand">TRUCKING COMPLIANCE</span>',
            subtitle: 'Comprehensive compliance, tax, and business services for the trucking industry.',
            cta: 'Contact Us ↓',
        },
        stats: {
            badge: 'Platform in Action',
            title: 'Already Helping Companies Stay Compliant',
            subtitle:
                'Our platform is actively managing fleets, drivers, and compliance for transport companies across the country.',
            carriers: 'Active Carriers',
            carriersSub: 'Managing their fleets',
            drivers: 'Registered Drivers',
            driversSub: 'Using the platform daily',
            docs: 'Documents Managed',
            docsSub: 'Securely stored and tracked',
            compliance: 'Compliance Rate',
            complianceSub: 'Audit-ready companies',
        },
        tabs: {
            title: 'Explore the Platform',
            subtitle: 'Manage every detail of your fleet with an interface designed for efficiency.',
            general: 'General',
            drivers: 'Drivers',
            testing: 'Drugs & Testing',
        },
        cta: {
            title: 'Ready to Join Our Growing Community?',
            subtitle:
                'Start managing your fleet with confidence. Get started today and see why carriers trust EFCTS.',
            carrier: 'Register as Carrier',
            driver: 'Register as Driver',
        },
        features: {
            title: 'Stay Audit-Ready, <span class="text-brand">Stay Profitable</span>',
            subtitle:
                'Our specialized platform helps carriers manage regulatory compliance and streamline driver operations in line with US regulations.',
            items: [
                {
                    title: 'Driver Management',
                    description:
                        'Complete driver lifecycle management with automated document verification.',
                    bullets: [
                        'Automated background checks',
                        'DOT compliance management',
                        'Qualification file maintenance',
                        'License expiration tracking',
                        'Digital application forms',
                    ],
                    dotClass: 'bg-brand',
                },
                {
                    title: 'Compliance & Reporting',
                    description:
                        'Stay prepared for DOT audits with comprehensive compliance reports.',
                    bullets: [
                        'Real-time compliance monitoring',
                        'Automated audit reports',
                        'Regulatory updates',
                        'DOT-compliant document retention',
                        'FMCSA safety rating tools',
                    ],
                    dotClass: 'bg-green-400',
                },
                {
                    title: 'Hours of Service',
                    description: 'Monitor driver hours and breaks to ensure FMCSA compliance.',
                    bullets: [
                        'ELD/HOS compliance monitoring',
                        'Break and rest period tracking',
                        'Violation risk alerts',
                        'Driver duty status logs',
                        'Automated RODS reporting',
                    ],
                    dotClass: 'bg-amber-400',
                },
                {
                    title: 'Vehicle Management',
                    description: 'Comprehensive fleet maintenance and inspection tracking.',
                    bullets: [
                        'Preventive maintenance scheduling',
                        'Vehicle inspection reports',
                        'DVIR system integration',
                        'Registration and permit tracking',
                    ],
                    dotClass: 'bg-orange-400',
                },
                {
                    title: 'Smart Alerts',
                    description: 'Never miss an important date with proactive notifications.',
                    bullets: [
                        'Expiration date reminders',
                        'Compliance deadline alerts',
                        'Document renewal notices',
                        'Custom notification rules',
                    ],
                    dotClass: 'bg-blue-500',
                },
            ],
        },
        pricing: {
            title: 'Plans Tailored to <span class="text-brand">Your Needs</span>',
            subtitle: 'Choose the plan that best fits your fleet size and specific requirements.',
            popular: 'Most Popular',
            getStarted: 'Get Started',
            customTitle: 'Need a custom solution?',
            customDescription:
                'Contact us to discuss your specific needs and create a tailored plan for your company.',
            contactSales: 'Contact Sales',
            month: '/month',
            beginner: 'For small fleets',
            intermediate: 'For medium fleets',
            pro: 'For growing fleets',
        },
        testimonials: {
            title: 'What Our <span class="text-brand">Clients Say</span>',
            subtitle:
                'Transportation companies across the country trust EFCTS to optimize their operations.',
            items: [
                {
                    quote:
                        '"Since implementing EFCTS, we have reduced our operating costs by 20% and significantly improved our fleet efficiency. The technical support is exceptional."',
                    author: 'Carlos Rodriguez',
                    role: 'Operations Director, Transportes XYZ',
                },
                {
                    quote:
                        '"The platform is intuitive and easy to use. The support team is excellent and always available to help us with any questions. It has transformed our operation."',
                    author: 'Maria Gonzalez',
                    role: 'CEO, Fast Logistics',
                },
                {
                    quote:
                        '"Route optimization has allowed us to save fuel and time. Our customers are more satisfied with more accurate delivery times and real-time visibility."',
                    author: 'Javier Lopez',
                    role: 'Fleet Manager, National Transport',
                },
            ],
        },
        onboarding: {
            title: 'Ready to Move Forward?',
            subtitle:
                'Choose the path that fits your operation and start working with EFCTS right away.',
            carrierTitle: 'Carrier Registration',
            carrierDescription:
                'Create your carrier account, start the wizard, and centralize drivers, vehicles, compliance, and documents in one place.',
            carrierBullets: [
                'Complete carrier onboarding wizard',
                'Invite team members and drivers',
                'Manage compliance in one dashboard',
            ],
            driverTitle: 'Driver Registration',
            driverDescription:
                'Drivers can register directly and complete their application with guided steps and document uploads.',
            driverBullets: [
                'Independent driver registration',
                'Application and document upload flow',
                'Faster qualification review',
            ],
            contactTitle: 'Need Help First?',
            contactDescription:
                'If you prefer, we can review your operation first and recommend the best starting point for your company.',
            contactBullets: [
                'Talk with our team',
                'Review your fleet size and needs',
                'Get a recommended plan',
            ],
            carrierCta: 'Start Carrier Registration',
            driverCta: 'Start Driver Registration',
            contactCta: 'Go to Contact Form',
        },
        contact: {
            title: 'Contact Us',
            subtitle:
                'Contact us today for a personalized demo and discover how EFCTS can transform your transportation operations.',
            success: 'Thank you! We will contact you shortly.',
            fullName: 'Full Name *',
            company: 'Company',
            email: 'Email *',
            phone: 'Phone',
            message: 'Message',
            send: 'Send Request',
            placeholders: {
                name: 'Your full name',
                company: 'Your company name',
                message: 'How can we help you?',
            },
            labels: {
                phone: 'Phone',
                email: 'Email',
                whatsapp: 'Send a message',
            },
        },
        modal: {
            title: 'Request Plan',
            success: 'Thank you! We will contact you shortly about this plan.',
            fullName: 'Full Name *',
            email: 'Email *',
            company: 'Company',
            phone: 'Phone',
            submit: 'Submit Request',
            placeholders: {
                name: 'Your full name',
                company: 'Your company name',
            },
            customSubtitle: "Tell us about your needs and we'll create a custom plan.",
        },
        misc: {
            sending: 'Sending...',
            genericError: 'An error occurred. Please try again.',
            networkError: 'Network error. Please try again.',
            openMenu: 'Open menu',
            closeMenu: 'Close menu',
        },
    },
    es: {
        nav: {
            features: 'Caracteristicas',
            pricing: 'Precios',
            testimonials: 'Testimonios',
            contact: 'Contacto',
            login: 'Iniciar Sesion',
            dashboard: 'Dashboard',
            contactBtn: 'Contacto',
            registerCarrier: 'Registrarse como Carrier',
            registerDriver: 'Registrarse como Conductor',
        },
        hero: {
            title: 'TU SOCIO DE CONFIANZA EN <br><span class="text-brand">CUMPLIMIENTO DE TRANSPORTE</span>',
            subtitle: 'Servicios integrales de cumplimiento, impuestos y negocios para la industria del transporte.',
            cta: 'Contactanos ↓',
        },
        stats: {
            badge: 'Plataforma en Accion',
            title: 'Ya Ayudando a Empresas a Mantenerse en Cumplimiento',
            subtitle:
                'Nuestra plataforma gestiona activamente flotas, conductores y cumplimiento para empresas de transporte en todo el pais.',
            carriers: 'Carriers Activos',
            carriersSub: 'Gestionando sus flotas',
            drivers: 'Conductores Registrados',
            driversSub: 'Usando la plataforma diariamente',
            docs: 'Documentos Gestionados',
            docsSub: 'Almacenados y rastreados de forma segura',
            compliance: 'Tasa de Cumplimiento',
            complianceSub: 'Empresas listas para auditoria',
        },
        tabs: {
            title: 'Explora la Plataforma',
            subtitle: 'Gestiona cada detalle de tu flota con una interfaz diseñada para la eficiencia.',
            general: 'General',
            drivers: 'Conductores',
            testing: 'Drogas y Pruebas',
        },
        cta: {
            title: '¿Listo para Unirte a Nuestra Comunidad en Crecimiento?',
            subtitle:
                'Comienza a gestionar tu flota con confianza. Empieza hoy y descubre por que los carriers confian en EFCTS.',
            carrier: 'Registrarse como Carrier',
            driver: 'Registrarse como Conductor',
        },
        features: {
            title: 'Mantente Listo para Auditoria, <span class="text-brand">Mantente Rentable</span>',
            subtitle:
                'Nuestra plataforma especializada ayuda a los carriers a gestionar el cumplimiento regulatorio y optimizar las operaciones de conductores segun las regulaciones de EE.UU.',
            items: [
                {
                    title: 'Gestion de Conductores',
                    description:
                        'Gestion completa del ciclo de vida del conductor con verificacion automatizada de documentos.',
                    bullets: [
                        'Verificacion automatizada de antecedentes',
                        'Gestion de cumplimiento DOT',
                        'Mantenimiento de archivos de calificacion',
                        'Seguimiento de vencimiento de licencias',
                        'Formularios de solicitud digital',
                    ],
                    dotClass: 'bg-brand',
                },
                {
                    title: 'Cumplimiento y Reportes',
                    description:
                        'Mantente preparado para auditorias DOT con reportes de cumplimiento completos.',
                    bullets: [
                        'Monitoreo de cumplimiento en tiempo real',
                        'Reportes de auditoria automatizados',
                        'Actualizaciones regulatorias',
                        'Retencion de documentos conforme a DOT',
                        'Herramientas de calificacion de seguridad FMCSA',
                    ],
                    dotClass: 'bg-green-400',
                },
                {
                    title: 'Horas de Servicio',
                    description:
                        'Monitorea las horas y descansos de los conductores para asegurar el cumplimiento FMCSA.',
                    bullets: [
                        'Monitoreo de cumplimiento ELD/HOS',
                        'Seguimiento de periodos de descanso',
                        'Alertas de riesgo de violacion',
                        'Registros de estado de servicio del conductor',
                        'Reportes RODS automatizados',
                    ],
                    dotClass: 'bg-amber-400',
                },
                {
                    title: 'Gestion de Vehiculos',
                    description: 'Mantenimiento integral de flota y seguimiento de inspecciones.',
                    bullets: [
                        'Programacion de mantenimiento preventivo',
                        'Reportes de inspeccion de vehiculos',
                        'Integracion con sistema DVIR',
                        'Seguimiento de registro y permisos',
                    ],
                    dotClass: 'bg-orange-400',
                },
                {
                    title: 'Alertas Inteligentes',
                    description: 'Nunca pierdas una fecha importante con notificaciones proactivas.',
                    bullets: [
                        'Recordatorios de fechas de vencimiento',
                        'Alertas de plazos de cumplimiento',
                        'Avisos de renovacion de documentos',
                        'Reglas de notificacion personalizadas',
                    ],
                    dotClass: 'bg-blue-500',
                },
            ],
        },
        pricing: {
            title: 'Planes Adaptados a <span class="text-brand">Tus Necesidades</span>',
            subtitle: 'Elige el plan que mejor se adapte al tamano de tu flota y requisitos especificos.',
            popular: 'Mas Popular',
            getStarted: 'Comenzar',
            customTitle: '¿Necesitas una solucion personalizada?',
            customDescription:
                'Contactanos para discutir tus necesidades especificas y crear un plan a medida para tu empresa.',
            contactSales: 'Contactar Ventas',
            month: '/mes',
            beginner: 'Para flotas pequenas',
            intermediate: 'Para flotas medianas',
            pro: 'Para flotas en crecimiento',
        },
        testimonials: {
            title: 'Lo Que Dicen Nuestros <span class="text-brand">Clientes</span>',
            subtitle:
                'Empresas de transporte en todo el pais confian en EFCTS para optimizar sus operaciones.',
            items: [
                {
                    quote:
                        '"Desde que implementamos EFCTS, hemos reducido nuestros costos operativos en un 20% y mejorado significativamente la eficiencia de nuestra flota. El soporte tecnico es excepcional."',
                    author: 'Carlos Rodriguez',
                    role: 'Director de Operaciones, Transportes XYZ',
                },
                {
                    quote:
                        '"La plataforma es intuitiva y facil de usar. El equipo de soporte es excelente y siempre esta disponible para ayudarnos con cualquier consulta. Ha transformado nuestra operacion."',
                    author: 'Maria Gonzalez',
                    role: 'CEO, Fast Logistics',
                },
                {
                    quote:
                        '"La optimizacion de rutas nos ha permitido ahorrar combustible y tiempo. Nuestros clientes estan mas satisfechos con tiempos de entrega mas precisos y visibilidad en tiempo real."',
                    author: 'Javier Lopez',
                    role: 'Gerente de Flota, National Transport',
                },
            ],
        },
        onboarding: {
            title: '¿Listo para avanzar?',
            subtitle:
                'Elige el camino que mejor se adapte a tu operacion y empieza a trabajar con EFCTS de inmediato.',
            carrierTitle: 'Registro de Carrier',
            carrierDescription:
                'Crea tu cuenta de carrier, inicia el wizard y centraliza drivers, vehiculos, compliance y documentos en un solo lugar.',
            carrierBullets: [
                'Wizard completo de onboarding',
                'Invita usuarios y drivers',
                'Administra compliance desde un solo dashboard',
            ],
            driverTitle: 'Registro de Conductor',
            driverDescription:
                'Los drivers pueden registrarse directamente y completar su aplicacion con pasos guiados y carga de documentos.',
            driverBullets: [
                'Registro independiente de driver',
                'Flujo de aplicacion y documentos',
                'Revision mas rapida de calificacion',
            ],
            contactTitle: '¿Necesitas ayuda primero?',
            contactDescription:
                'Si prefieres, podemos revisar tu operacion primero y recomendarte el mejor punto de inicio para tu empresa.',
            contactBullets: [
                'Habla con nuestro equipo',
                'Revisamos el tamaño de tu flotilla',
                'Te recomendamos el plan adecuado',
            ],
            carrierCta: 'Iniciar registro de carrier',
            driverCta: 'Iniciar registro de conductor',
            contactCta: 'Ir al formulario de contacto',
        },
        contact: {
            title: 'Contactanos',
            subtitle:
                'Contactanos hoy para una demostracion personalizada y descubre como EFCTS puede transformar tus operaciones de transporte.',
            success: '¡Gracias! Nos pondremos en contacto contigo pronto.',
            fullName: 'Nombre Completo *',
            company: 'Empresa',
            email: 'Correo Electronico *',
            phone: 'Telefono',
            message: 'Mensaje',
            send: 'Enviar Solicitud',
            placeholders: {
                name: 'Tu nombre completo',
                company: 'Nombre de tu empresa',
                message: '¿Como podemos ayudarte?',
            },
            labels: {
                phone: 'Telefono',
                email: 'Correo',
                whatsapp: 'Enviar un mensaje',
            },
        },
        modal: {
            title: 'Solicitar Plan',
            success: '¡Gracias! Nos pondremos en contacto contigo pronto sobre este plan.',
            fullName: 'Nombre Completo *',
            email: 'Correo Electronico *',
            company: 'Empresa',
            phone: 'Telefono',
            submit: 'Enviar Solicitud',
            placeholders: {
                name: 'Tu nombre completo',
                company: 'Nombre de tu empresa',
            },
            customSubtitle: 'Cuentanos sobre tus necesidades y crearemos un plan personalizado.',
        },
        misc: {
            sending: 'Enviando...',
            genericError: 'Ocurrio un error. Por favor intenta de nuevo.',
            networkError: 'Error de red. Por favor intenta de nuevo.',
            openMenu: 'Abrir menu',
            closeMenu: 'Cerrar menu',
        },
    },
} as const

const t = computed(() => translations[locale.value])

const heroPrimaryCta = computed(() => (locale.value === 'es' ? 'Contactanos' : 'Contact Us'))

const processSection = computed(() =>
    locale.value === 'es'
        ? {
              title: 'Como Funciona',
              subtitle:
                  'Un camino simple para organizar tu operacion, mantener compliance y avanzar mas rapido.',
              steps: [
                  {
                      title: 'Registra tu cuenta',
                      description:
                          'Crea tu cuenta de carrier o driver y comienza el flujo guiado de onboarding.',
                  },
                  {
                      title: 'Completa tu configuracion',
                      description:
                          'Sube documentos, asigna drivers y vehiculos, y centraliza los registros importantes.',
                  },
                  {
                      title: 'Opera con confianza',
                      description:
                          'Da seguimiento a vencimientos, mantenimientos, HOS y alertas desde una sola plataforma.',
                  },
              ],
          }
        : {
              title: 'How It Works',
              subtitle: 'A simple path to get your operation organized, compliant, and moving faster.',
              steps: [
                  {
                      title: 'Register Your Account',
                      description:
                          'Create your carrier or driver account and start the guided onboarding flow.',
                  },
                  {
                      title: 'Complete Compliance Setup',
                      description:
                          'Upload documents, assign drivers and vehicles, and centralize the records that matter.',
                  },
                  {
                      title: 'Operate With Confidence',
                      description:
                          'Track expirations, maintenance, HOS, and alerts from one platform built for trucking.',
                  },
              ],
          },
)

const contactSide = computed(() =>
    locale.value === 'es'
        ? {
              title: 'Habla con el equipo EFCTS',
              description:
                  'Ya sea que estes iniciando, creciendo o poniendo tu compliance en orden, podemos ayudarte a definir el siguiente paso.',
              body: 'EFCTS ayuda a carriers y drivers a centralizar compliance, documentos, operaciones y preparacion en un solo lugar.',
          }
        : {
              title: 'Talk With the EFCTS Team',
              description:
                  'Whether you are launching, scaling, or cleaning up compliance, we can help you choose the right next step.',
              body: 'EFCTS helps carriers and drivers centralize compliance, documents, operations, and readiness in one place.',
          },
)

const platformTabs = computed(() => [
    { key: 'general' as const, label: t.value.tabs.general, image: '/img/landing/1_general.png' },
    { key: 'drivers' as const, label: t.value.tabs.drivers, image: '/img/landing/2_drivers.png' },
    { key: 'testing' as const, label: t.value.tabs.testing, image: '/img/landing/3_vehicle_drugs.png' },
])

const activeTab = computed(() => platformTabs.value.find((tab) => tab.key === selectedTab.value) ?? platformTabs.value[0])

const flash = computed(() => (page.props.flash ?? {}) as Record<string, string | null>)

const dashboardHref = computed(() => {
    const roles = authUser.value?.roles ?? []
    if (roles.includes('superadmin') || roles.includes('admin')) return route('admin.dashboard')
    if (roles.includes('user_carrier')) return route('carrier.dashboard')
    if (roles.includes('user_driver')) return route('driver.dashboard')
    return route('login')
})

const contactForm = useForm({
    full_name: '',
    company: '',
    email: '',
    phone: '',
    message: '',
})

const planForm = useForm({
    full_name: '',
    company: '',
    email: '',
    phone: '',
    plan_name: '',
    plan_price: null as number | null,
})

const pricingCards = computed(() => {
    const descriptors = [t.value.pricing.beginner, t.value.pricing.intermediate, t.value.pricing.pro]

    return plans.value.map((plan, index) => ({
        ...plan,
        caption: descriptors[index] ?? plan.description ?? '',
    }))
})

const planFeatures = (plan: LandingPlan) => [
    `${plan.max_users} ${locale.value === 'es' ? 'usuarios de plataforma' : 'platform users'}`,
    `${plan.max_drivers} ${locale.value === 'es' ? 'conductores' : 'drivers management'}`,
    `${plan.max_vehicles} ${locale.value === 'es' ? 'vehiculos en el sistema' : 'vehicles in the system'}`,
    ...(plan.max_drivers >= 15
        ? [
              locale.value === 'es' ? 'Gestion avanzada de documentos' : 'Advanced document management',
              locale.value === 'es' ? 'Soporte prioritario 24/7' : '24/7 priority support',
          ]
        : plan.max_drivers >= 10
          ? [
                locale.value === 'es'
                    ? 'Herramientas avanzadas de cumplimiento'
                    : 'Advanced compliance tools',
                locale.value === 'es'
                    ? 'Soporte prioritario por correo y telefono'
                    : 'Priority email & phone support',
            ]
          : [
                locale.value === 'es' ? 'Reportes de cumplimiento' : 'Compliance reporting',
                locale.value === 'es' ? 'Soporte por correo' : 'Email support',
            ]),
]

const formatCurrency = (value: number) =>
    new Intl.NumberFormat(locale.value === 'es' ? 'es-MX' : 'en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value)

const numberLabel = (value: number) =>
    new Intl.NumberFormat(locale.value === 'es' ? 'es-MX' : 'en-US').format(value)

const openPlanModal = (plan: LandingPlan | { name: string; price: number }) => {
    const normalized = {
        id: 'custom',
        description: null,
        pricing_type: 'monthly',
        max_users: 0,
        max_drivers: 0,
        max_vehicles: 0,
        is_popular: false,
        ...plan,
    }

    selectedPlan.value = normalized
    planForm.plan_name = normalized.name
    planForm.plan_price = normalized.price
    planRequestOpen.value = true
}

const closePlanModal = () => {
    planRequestOpen.value = false
    selectedPlan.value = null
}

const planSubtitle = computed(() => {
    if (!selectedPlan.value) return ''
    if (selectedPlan.value.name.toLowerCase() === 'custom') return t.value.modal.customSubtitle
    return `${selectedPlan.value.name} Plan — ${formatCurrency(selectedPlan.value.price)}${t.value.pricing.month}`
})

const submitContact = () => {
    contactForm.post(route('landing.contact.store'), {
        preserveScroll: true,
        onSuccess: () => {
            contactForm.reset()
        },
    })
}

const submitPlanRequest = () => {
    planForm.post(route('landing.plan-request.store'), {
        preserveScroll: true,
        onSuccess: () => {
            planForm.reset()
            closePlanModal()
        },
        onError: () => {
            planRequestOpen.value = true
        },
    })
}

const handleScroll = () => {
    headerScrolled.value = window.scrollY > 50
}

const setupFadeAnimations = () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible')
                }
            })
        },
        { threshold: 0.1 },
    )

    document.querySelectorAll('.fade-up').forEach((element) => observer.observe(element))
}

watch(locale, (value) => {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem('efcts_lang', value)
        document.documentElement.lang = value
    }
})

onMounted(() => {
    if (typeof window === 'undefined') return

    const savedLocale = window.localStorage.getItem('efcts_lang')
    if (savedLocale === 'en' || savedLocale === 'es') locale.value = savedLocale

    document.documentElement.lang = locale.value
    handleScroll()
    window.addEventListener('scroll', handleScroll)
    setupFadeAnimations()
})

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('scroll', handleScroll)
    }
})
</script>

<template>
    <Head title="EFCTS | Trucking Compliance Reimagined" />

    <div class="bg-[#050505] text-white">
        <FlashMessages />

        <header
            id="main-header"
            class="fixed z-50 w-full border-b px-6 py-5 transition-all duration-300 md:px-8"
            :class="
                headerScrolled
                    ? 'border-white/10 bg-black/80 backdrop-blur-lg'
                    : 'border-white/5 bg-black/20 backdrop-blur-md'
            "
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex items-center gap-2">
                    <img src="/img/landing/logo_efcts.png" class="h-8 w-8 object-contain" alt="EFCTS" />
                    <span class="text-xl font-extrabold uppercase tracking-tighter">EFCTS</span>
                </div>

                <nav class="hidden gap-10 text-[10px] font-bold uppercase tracking-[0.2em] lg:flex">
                    <a href="#features" class="nav-link-new">{{ t.nav.features }}</a>
                    <a href="#pricing" class="nav-link-new">{{ t.nav.pricing }}</a>
                    <a href="#testimonials" class="nav-link-new">{{ t.nav.testimonials }}</a>
                    <a href="#contact" class="nav-link-new">{{ t.nav.contact }}</a>
                </nav>

                <div class="hidden items-center gap-6 lg:flex">
                    <button
                        type="button"
                        class="rounded border border-white/20 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest transition hover:border-white/50"
                        @click="locale = locale === 'en' ? 'es' : 'en'"
                    >
                        {{ locale === 'en' ? 'ES' : 'EN' }}
                    </button>

                    <Link
                        :href="dashboardHref"
                        class="text-[10px] font-bold uppercase tracking-widest opacity-60 transition hover:opacity-100"
                    >
                        {{ authUser ? t.nav.dashboard : t.nav.login }}
                    </Link>

                    <a href="#contact" class="btn-brand px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest text-white">
                        {{ t.nav.contactBtn }}
                    </a>
                </div>

                <div class="lg:hidden">
                    <button
                        type="button"
                        class="flex h-8 w-8 flex-col items-center justify-center gap-1.5"
                        :aria-label="t.misc.openMenu"
                        @click="mobileMenuOpen = true"
                    >
                        <span class="h-0.5 w-6 bg-white"></span>
                        <span class="h-0.5 w-5 bg-white"></span>
                        <span class="h-0.5 w-4 bg-white"></span>
                    </button>
                </div>
            </div>
        </header>

        <div
            class="mobile-menu-panel fixed right-0 top-0 z-[60] h-full w-4/5 bg-[#0a0a0a] p-8 lg:hidden"
            :class="{ active: mobileMenuOpen }"
        >
            <div class="mb-10 flex justify-end">
                <button type="button" class="text-white" :aria-label="t.misc.closeMenu" @click="mobileMenuOpen = false">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <div class="mb-6">
                <button
                    type="button"
                    class="rounded border border-white/20 px-4 py-2 text-sm font-bold uppercase tracking-widest text-white/70 transition hover:border-white/50 hover:text-white"
                    @click="locale = locale === 'en' ? 'es' : 'en'"
                >
                    {{ locale === 'en' ? 'ES' : 'EN' }}
                </button>
            </div>

            <nav class="space-y-6">
                <a href="#features" class="block mobile-link" @click="mobileMenuOpen = false">{{ t.nav.features }}</a>
                <a href="#pricing" class="block mobile-link" @click="mobileMenuOpen = false">{{ t.nav.pricing }}</a>
                <a href="#testimonials" class="block mobile-link" @click="mobileMenuOpen = false">{{ t.nav.testimonials }}</a>
                <a href="#contact" class="block mobile-link" @click="mobileMenuOpen = false">{{ t.nav.contact }}</a>

                <div class="mt-6 space-y-4 border-t border-white/10 pt-6">
                    <Link :href="dashboardHref" class="block mobile-link">
                        {{ authUser ? t.nav.dashboard : t.nav.login }}
                    </Link>

                    <Link
                        v-if="!authUser && canRegister"
                        :href="route('carrier.register')"
                        class="block mobile-link"
                    >
                        {{ t.nav.registerCarrier }}
                    </Link>

                    <Link
                        v-if="!authUser && canRegister"
                        :href="route('driver.register.select')"
                        class="block mobile-link"
                    >
                        {{ t.nav.registerDriver }}
                    </Link>
                </div>
            </nav>

            <div class="mt-10">
                <a href="#contact" class="btn-brand block px-5 py-3 text-center text-xs font-bold uppercase tracking-widest text-white" @click="mobileMenuOpen = false">
                    {{ t.nav.contactBtn }}
                </a>
            </div>

            <div class="mt-auto pt-10 text-[9px] uppercase tracking-widest text-gray-600">
                <p>{{ contact.phone }}</p>
                <p class="mt-1">{{ contact.address }}</p>
            </div>
        </div>

        <section class="relative flex h-screen items-center justify-center overflow-hidden">
            <video class="absolute h-full w-full object-cover" autoplay muted loop playsinline>
                <source src="/img/landing/video_efcts.mp4" type="video/mp4" />
            </video>
            <div class="video-overlay absolute inset-0"></div>

            <div class="relative z-10 mt-20 px-6 text-center">
                <h1
                    class="mb-8 text-5xl font-extrabold uppercase leading-[1] tracking-tighter md:text-8xl"
                    v-html="t.hero.title"
                />
                <p class="mx-auto mb-10 max-w-2xl text-lg font-medium text-gray-200 drop-shadow-lg md:text-xl">
                    {{ t.hero.subtitle }}
                </p>
                <div class="flex flex-col items-center justify-center gap-6 sm:flex-row">
                    <a href="#contact" class="btn-brand px-10 py-4 text-xs font-bold uppercase tracking-[0.2em] text-white shadow-2xl">
                        {{ heroPrimaryCta }}
                    </a>
                    <a href="tel:4328535493" class="border-b border-white/30 pb-1 text-sm font-bold uppercase tracking-widest transition-all hover:border-white">
                        {{ contact.phone }}
                    </a>
                </div>
                <div
                    v-if="!authUser && canRegister"
                    class="mt-6 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        :href="route('carrier.register')"
                        class="inline-flex items-center justify-center border border-white/30 bg-white/10 px-7 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-white transition hover:bg-white/20"
                    >
                        {{ t.nav.registerCarrier }}
                    </Link>
                    <Link
                        :href="route('driver.register.select')"
                        class="inline-flex items-center justify-center border border-white/20 px-7 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-white transition hover:border-white/40 hover:bg-white/10"
                    >
                        {{ t.nav.registerDriver }}
                    </Link>
                </div>
            </div>
        </section>

        <section class="w-full bg-white py-20 text-black md:py-24">
            <div class="container mx-auto px-4 md:px-6">
                <div class="mb-16 flex flex-col items-center justify-center space-y-4 text-center">
                    <span class="inline-flex items-center rounded-md bg-success px-3 py-1.5 text-sm font-medium text-white">
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        <span>{{ t.stats.badge }}</span>
                    </span>
                    <h2 class="max-w-3xl text-3xl font-bold tracking-tight text-primary md:text-4xl lg:text-5xl">
                        {{ t.stats.title }}
                    </h2>
                    <p class="max-w-[800px] text-xl text-gray-600">
                        {{ t.stats.subtitle }}
                    </p>
                </div>

                <div class="mb-16 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <div class="rounded-2xl border border-primary p-8 text-center transition-all hover:shadow-xl">
                        <div class="mb-2 text-5xl font-bold text-primary">{{ numberLabel(stats.activeCarriers) }}+</div>
                        <div class="font-medium text-gray-700">{{ t.stats.carriers }}</div>
                        <div class="mt-2 text-sm text-gray-600">{{ t.stats.carriersSub }}</div>
                    </div>
                    <div class="rounded-2xl border border-success p-8 text-center transition-all hover:shadow-xl">
                        <div class="mb-2 text-5xl font-bold text-success">{{ numberLabel(stats.registeredDrivers) }}+</div>
                        <div class="font-medium text-gray-700">{{ t.stats.drivers }}</div>
                        <div class="mt-2 text-sm text-gray-600">{{ t.stats.driversSub }}</div>
                    </div>
                    <div class="rounded-2xl border border-warning p-8 text-center transition-all hover:shadow-xl">
                        <div class="mb-2 text-5xl font-bold text-warning">{{ numberLabel(stats.documentsManaged) }}+</div>
                        <div class="font-medium text-gray-700">{{ t.stats.docs }}</div>
                        <div class="mt-2 text-sm text-gray-600">{{ t.stats.docsSub }}</div>
                    </div>
                    <div class="rounded-2xl border border-success p-8 text-center transition-all hover:shadow-xl">
                        <div class="mb-2 text-5xl font-bold text-success">{{ stats.complianceRate }}%</div>
                        <div class="font-medium text-gray-700">{{ t.stats.compliance }}</div>
                        <div class="mt-2 text-sm text-gray-600">{{ t.stats.complianceSub }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-24 text-black">
            <div class="mx-auto grid max-w-7xl items-center gap-10 px-8 lg:grid-cols-[0.88fr_1.12fr]">
                <div class="fade-up">
                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                        <Lucide icon="LayoutDashboard" class="h-4 w-4 text-primary" />
                        Platform Preview
                    </div>
                    <h2 class="mt-5 text-4xl font-extrabold tracking-tighter text-[#050505] md:text-5xl">
                        {{ t.tabs.title }}
                    </h2>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                        {{ t.tabs.subtitle }}
                    </p>

                    <div class="mt-8 space-y-3">
                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="mt-0.5 rounded-lg bg-primary/10 p-2 text-primary">
                                <Lucide icon="Users" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Driver workflows in one place</p>
                                <p class="text-sm text-slate-500">Documents, qualifications, alerts, and history without jumping between tools.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="mt-0.5 rounded-lg bg-success/10 p-2 text-success">
                                <Lucide icon="ShieldCheck" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Compliance visibility</p>
                                <p class="text-sm text-slate-500">See expiring items, safety requirements, and action points from the same dashboard.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <div class="mt-0.5 rounded-lg bg-info/10 p-2 text-info">
                                <Lucide icon="Truck" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Fleet operations with context</p>
                                <p class="text-sm text-slate-500">Vehicles, maintenance, inspections, testing, and records tied together the way carriers actually work.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <button
                            v-for="tab in platformTabs"
                            :key="tab.key"
                            type="button"
                            class="inline-flex items-center rounded-lg border px-5 py-2.5 text-sm font-semibold transition"
                            :class="
                                selectedTab === tab.key
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'
                            "
                            @click="selectedTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <div class="fade-up">
                    <div class="box box--stacked overflow-hidden border border-slate-200 bg-white p-0">
                        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">
                                <Lucide icon="MonitorSmartphone" class="h-4 w-4" />
                                {{ activeTab.label }}
                            </div>
                        </div>
                        <div class="border-b border-slate-200 bg-white p-4">
                            <img
                                :src="activeTab.image"
                                :alt="activeTab.label"
                                class="h-full w-full rounded-xl border border-slate-200 object-cover transition-all duration-700"
                            />
                        </div>
                        <div class="grid gap-3 bg-white p-4 sm:grid-cols-3">
                            <div
                                v-for="tab in platformTabs"
                                :key="`${tab.key}-meta`"
                                class="rounded-xl border px-4 py-3 text-left"
                                :class="
                                    selectedTab === tab.key
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-200 bg-slate-50/70'
                                "
                            >
                                <div class="text-[11px] font-bold uppercase tracking-[0.18em]" :class="selectedTab === tab.key ? 'text-primary' : 'text-slate-500'">
                                    {{ tab.label }}
                                </div>
                                <div class="mt-2 text-sm text-slate-600">
                                    {{ tab.key === 'general' ? 'Overview and compliance metrics' : tab.key === 'drivers' ? 'Driver records and workflows' : 'Testing, safety, and support files' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 px-8 pb-24">
            <div class="fade-up mx-auto grid max-w-7xl gap-6 rounded-2xl border border-slate-200 bg-[#071024] px-8 py-8 text-white lg:grid-cols-[1fr_auto] lg:items-center">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">
                        <Lucide icon="Rocket" class="h-4 w-4 text-primary" />
                        Get Started
                    </div>
                    <h3 class="mt-5 text-3xl font-extrabold tracking-tighter md:text-4xl">{{ t.cta.title }}</h3>
                    <p class="mt-4 text-lg leading-8 text-slate-300">{{ t.cta.subtitle }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <Link
                        :href="route('carrier.register')"
                        class="inline-flex min-w-[190px] items-center justify-center rounded-xl bg-white px-6 py-4 text-sm font-bold text-[#050505] transition hover:bg-slate-100"
                    >
                        {{ t.cta.carrier }}
                    </Link>
                    <Link
                        :href="route('driver.register.select')"
                        class="inline-flex min-w-[190px] items-center justify-center rounded-xl border border-white/20 px-6 py-4 text-sm font-bold text-white transition hover:bg-white/10"
                    >
                        {{ t.cta.driver }}
                    </Link>
                </div>
            </div>
        </section>

        <section id="features" class="bg-[#050505] px-8 py-32">
            <div class="mx-auto max-w-7xl">
                <div class="fade-up mb-20 text-center">
                    <h2 class="mb-6 text-4xl font-extrabold uppercase tracking-tighter md:text-5xl" v-html="t.features.title" />
                    <p class="mx-auto max-w-2xl text-gray-500">{{ t.features.subtitle }}</p>
                </div>

                <div class="fade-up grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in t.features.items"
                        :key="feature.title"
                        class="group border border-white/10 p-8 transition-all duration-300 hover:border-brand/50"
                    >
                        <h3 class="mb-4 text-[11px] font-bold uppercase tracking-widest">{{ feature.title }}</h3>
                        <p class="mb-6 text-sm text-gray-500">{{ feature.description }}</p>
                        <div class="space-y-3">
                            <div
                                v-for="bullet in feature.bullets"
                                :key="bullet"
                                class="flex items-center gap-3 text-sm text-gray-400"
                            >
                                <span class="h-1 w-1 flex-shrink-0 rounded-full" :class="feature.dotClass"></span>
                                <span>{{ bullet }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#0b1020] px-8 py-24 text-white">
            <div class="mx-auto max-w-7xl">
                <div class="fade-up mb-14 text-center">
                    <h2 class="text-4xl font-extrabold tracking-tighter md:text-5xl">{{ processSection.title }}</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-slate-400">{{ processSection.subtitle }}</p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <article
                        v-for="(step, index) in processSection.steps"
                        :key="step.title"
                        class="fade-up rounded-[1.75rem] border border-white/10 bg-white/[0.03] p-8"
                    >
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">
                            {{ index + 1 }}
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-white">{{ step.title }}</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-400">{{ step.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="pricing" class="bg-white px-8 py-32 text-black">
            <div class="mx-auto max-w-7xl">
                <div class="fade-up mb-16 text-center">
                    <h2 class="mb-4 text-4xl font-extrabold uppercase tracking-tighter text-[#050505] md:text-5xl" v-html="t.pricing.title" />
                    <p class="mx-auto max-w-xl text-gray-500">{{ t.pricing.subtitle }}</p>
                </div>

                <div class="fade-up grid gap-8 md:grid-cols-3">
                    <div
                        v-for="(plan, index) in pricingCards"
                        :key="plan.id"
                        class="group relative border p-8 transition-all"
                        :class="plan.is_popular ? 'border-2 border-brand' : 'border-gray-200 hover:border-brand/40'"
                    >
                        <div
                            v-if="plan.is_popular"
                            class="absolute left-0 right-0 top-0 bg-brand py-1.5 text-center text-[9px] font-bold uppercase tracking-widest text-white"
                        >
                            {{ t.pricing.popular }}
                        </div>
                        <div :class="plan.is_popular ? 'pt-4' : ''">
                            <h3 class="mb-2 text-[11px] font-bold uppercase tracking-widest text-gray-400">
                                {{ plan.name }}
                            </h3>
                            <p class="mb-4 text-sm text-gray-500">{{ plan.caption }}</p>
                            <div class="mb-6 flex items-baseline">
                                <span class="text-5xl font-extrabold" :class="plan.is_popular ? 'text-brand' : 'text-[#050505]'">
                                    {{ formatCurrency(plan.price) }}
                                </span>
                                <span class="ml-1 text-sm text-gray-400">{{ t.pricing.month }}</span>
                            </div>
                            <ul class="mb-8 space-y-3">
                                <li
                                    v-for="feature in planFeatures(plan)"
                                    :key="feature"
                                    class="flex items-center gap-3 text-sm text-gray-600"
                                >
                                    <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand"></span>
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                            <button
                                type="button"
                                class="block w-full py-3 text-center text-xs font-bold uppercase tracking-widest transition-all"
                                :class="
                                    plan.is_popular
                                        ? 'btn-brand text-white'
                                        : 'border-2 border-[#050505] text-[#050505] hover:bg-[#050505] hover:text-white'
                                "
                                @click="openPlanModal(plan)"
                            >
                                {{ t.pricing.getStarted }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="fade-up mt-16 flex flex-col items-center gap-8 border border-gray-200 p-8 md:flex-row">
                    <div class="md:w-2/3">
                        <h3 class="mb-2 text-xl font-extrabold uppercase tracking-tighter text-[#050505]">
                            {{ t.pricing.customTitle }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ t.pricing.customDescription }}</p>
                    </div>
                    <div class="flex justify-center md:w-1/3">
                        <button
                            type="button"
                            class="btn-brand px-8 py-3 text-xs font-bold uppercase tracking-widest text-white"
                            @click="openPlanModal({ name: 'Custom', price: 0 })"
                        >
                            {{ t.pricing.contactSales }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section id="testimonials" class="bg-[#050505] px-8 py-32">
            <div class="mx-auto max-w-7xl">
                <div class="fade-up mb-16 text-center">
                    <h2 class="mb-4 text-4xl font-extrabold uppercase tracking-tighter md:text-5xl" v-html="t.testimonials.title" />
                    <p class="mx-auto max-w-xl text-gray-500">{{ t.testimonials.subtitle }}</p>
                </div>

                <div class="fade-up grid gap-8 md:grid-cols-3">
                    <div
                        v-for="testimonial in t.testimonials.items"
                        :key="testimonial.author"
                        class="border border-white/10 p-8 transition-all hover:border-brand/30"
                    >
                        <p class="mb-8 text-sm italic leading-relaxed text-gray-400">{{ testimonial.quote }}</p>
                        <div class="border-t border-white/10 pt-6">
                            <h4 class="text-sm font-bold uppercase tracking-widest">{{ testimonial.author }}</h4>
                            <p class="mt-1 text-[10px] uppercase tracking-widest text-gray-600">{{ testimonial.role }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#050505] px-8 pb-24">
            <div class="fade-up mx-auto max-w-7xl border border-white/10 bg-black/30 p-8 md:p-12">
                <div class="max-w-3xl">
                    <h2 class="text-3xl font-extrabold uppercase tracking-tighter md:text-4xl">
                        {{ t.onboarding.title }}
                    </h2>
                    <p class="mt-4 text-gray-400">
                        {{ t.onboarding.subtitle }}
                    </p>
                </div>

                <div class="mt-12 grid gap-6 lg:grid-cols-3">
                    <article class="border border-white/10 bg-white/[0.02] p-6">
                        <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-full bg-brand/15 text-brand">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-white">
                            {{ t.onboarding.carrierTitle }}
                        </h3>
                        <p class="mt-4 text-sm leading-7 text-gray-400">
                            {{ t.onboarding.carrierDescription }}
                        </p>
                        <ul class="mt-6 space-y-3">
                            <li
                                v-for="bullet in t.onboarding.carrierBullets"
                                :key="bullet"
                                class="flex items-center gap-3 text-sm text-gray-300"
                            >
                                <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-brand"></span>
                                <span>{{ bullet }}</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <Link
                                :href="route('carrier.register')"
                                class="btn-brand inline-flex w-full items-center justify-center px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white"
                            >
                                {{ t.onboarding.carrierCta }}
                            </Link>
                        </div>
                    </article>

                    <article class="border border-white/10 bg-white/[0.02] p-6">
                        <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white">
                            <User class="h-5 w-5" />
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-white">
                            {{ t.onboarding.driverTitle }}
                        </h3>
                        <p class="mt-4 text-sm leading-7 text-gray-400">
                            {{ t.onboarding.driverDescription }}
                        </p>
                        <ul class="mt-6 space-y-3">
                            <li
                                v-for="bullet in t.onboarding.driverBullets"
                                :key="bullet"
                                class="flex items-center gap-3 text-sm text-gray-300"
                            >
                                <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-white"></span>
                                <span>{{ bullet }}</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <Link
                                :href="route('driver.register.select')"
                                class="inline-flex w-full items-center justify-center border border-white/20 px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white transition hover:bg-white/10"
                            >
                                {{ t.onboarding.driverCta }}
                            </Link>
                        </div>
                    </article>

                    <article class="border border-white/10 bg-gradient-to-br from-white/[0.04] to-brand/10 p-6">
                        <div class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                            <Phone class="h-5 w-5" />
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-white">
                            {{ t.onboarding.contactTitle }}
                        </h3>
                        <p class="mt-4 text-sm leading-7 text-gray-300">
                            {{ t.onboarding.contactDescription }}
                        </p>
                        <ul class="mt-6 space-y-3">
                            <li
                                v-for="bullet in t.onboarding.contactBullets"
                                :key="bullet"
                                class="flex items-center gap-3 text-sm text-gray-200"
                            >
                                <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-emerald-400"></span>
                                <span>{{ bullet }}</span>
                            </li>
                        </ul>
                        <div class="mt-8 space-y-3">
                            <a
                                href="#contact"
                                class="btn-brand inline-flex w-full items-center justify-center px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white"
                            >
                                {{ t.onboarding.contactCta }}
                            </a>
                            <a
                                :href="contact.whatsapp_url"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex w-full items-center justify-center border border-white/20 px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-white transition hover:bg-white/10"
                            >
                                WhatsApp
                            </a>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="contact" class="bg-white px-8 py-32 text-black">
            <div class="fade-up mx-auto max-w-7xl">
                <div class="mb-12 text-center">
                    <h2 class="text-5xl font-extrabold uppercase tracking-tighter text-[#050505]">
                        {{ t.contact.title }}
                    </h2>
                    <p class="mx-auto mt-6 max-w-2xl text-gray-500">
                        {{ t.contact.subtitle }}
                    </p>
                </div>

                <div
                    v-if="flash.success"
                    class="mb-8 rounded border border-green-200 bg-green-50 p-6 text-center text-sm font-medium text-green-800"
                >
                    {{ flash.success }}
                </div>

                <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="box box--stacked border border-slate-200 bg-white p-8 md:p-10">
                        <form class="grid grid-cols-1 gap-8 md:grid-cols-2" @submit.prevent="submitContact">
                            <div class="flex flex-col gap-2">
                                <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ t.contact.fullName }}
                                </FormLabel>
                                <FormInput
                                    v-model="contactForm.full_name"
                                    :placeholder="t.contact.placeholders.name"
                                    class="bg-gray-50 p-4 text-sm text-gray-900 focus:border-[#08459f]"
                                />
                                <InputError :message="contactForm.errors.full_name" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ t.contact.company }}
                                </FormLabel>
                                <FormInput
                                    v-model="contactForm.company"
                                    :placeholder="t.contact.placeholders.company"
                                    class="bg-gray-50 p-4 text-sm text-gray-900 focus:border-[#08459f]"
                                />
                                <InputError :message="contactForm.errors.company" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ t.contact.email }}
                                </FormLabel>
                                <FormInput
                                    v-model="contactForm.email"
                                    type="email"
                                    placeholder="email@company.com"
                                    class="bg-gray-50 p-4 text-sm text-gray-900 focus:border-[#08459f]"
                                />
                                <InputError :message="contactForm.errors.email" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ t.contact.phone }}
                                </FormLabel>
                                <FormInput
                                    v-model="contactForm.phone"
                                    type="tel"
                                    placeholder="(000) 000-0000"
                                    class="bg-gray-50 p-4 text-sm text-gray-900 focus:border-[#08459f]"
                                />
                                <InputError :message="contactForm.errors.phone" />
                            </div>
                            <div class="flex flex-col gap-2 md:col-span-2">
                                <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                                    {{ t.contact.message }}
                                </FormLabel>
                                <FormTextarea
                                    v-model="contactForm.message"
                                    rows="5"
                                    :placeholder="t.contact.placeholders.message"
                                    class="h-32 bg-gray-50 p-4 text-sm text-gray-900 focus:border-[#08459f]"
                                />
                                <InputError :message="contactForm.errors.message" />
                            </div>
                            <button
                                type="submit"
                                class="btn-brand inline-flex items-center justify-center gap-2 md:col-span-2 py-5 text-xs font-bold uppercase tracking-[0.3em] text-white"
                                :disabled="contactForm.processing"
                            >
                                <Lucide icon="Send" class="h-4 w-4" />
                                {{ contactForm.processing ? t.misc.sending : t.contact.send }}
                            </button>
                        </form>
                    </div>

                    <div class="box box--stacked border border-slate-200 bg-white p-8 text-slate-800 md:p-10">
                        <div class="inline-flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-primary">
                            <Lucide icon="MessagesSquare" class="h-4 w-4" />
                            Contact EFCTS
                        </div>
                        <h3 class="mt-5 text-3xl font-extrabold tracking-tighter text-slate-900">{{ contactSide.title }}</h3>
                        <p class="mt-5 text-base leading-8 text-slate-500">
                            {{ contactSide.description }}
                        </p>

                        <div class="mt-10 space-y-6">
                            <a href="tel:+14328535493" class="block rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-primary/30 hover:bg-slate-50/80">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                                        <Lucide icon="Phone" class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ t.contact.labels.phone }}</div>
                                        <div class="mt-2 text-lg font-bold text-slate-900">{{ contact.phone }}</div>
                                    </div>
                                </div>
                            </a>
                            <a href="mailto:support@efcts.com" class="block rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-primary/30 hover:bg-slate-50/80">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                                        <Lucide icon="Mail" class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ t.contact.labels.email }}</div>
                                        <div class="mt-2 text-lg font-bold text-slate-900">{{ contact.email }}</div>
                                    </div>
                                </div>
                            </a>
                            <a
                                :href="contact.whatsapp_url"
                                target="_blank"
                                rel="noreferrer"
                                class="block rounded-xl border border-primary/30 bg-primary/5 p-5 transition hover:bg-primary/10"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                                        <Lucide icon="MessageCircle" class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">WhatsApp</div>
                                        <div class="mt-2 text-lg font-bold text-slate-900">{{ t.contact.labels.whatsapp }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="mt-10 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-600">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                    <Lucide icon="MapPin" class="h-5 w-5" />
                                </div>
                                <div>
                                    <p>{{ contact.address }}</p>
                                    <p class="mt-3">
                                        {{ contactSide.body }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="planRequestOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
            @click.self="closePlanModal"
        >
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto bg-white p-8 text-black">
                <button
                    type="button"
                    class="absolute right-4 top-4 text-2xl leading-none text-gray-400 transition hover:text-gray-700"
                    @click="closePlanModal"
                >
                    &times;
                </button>
                <h3 class="mb-1 text-xl font-extrabold uppercase tracking-tighter text-[#050505]">
                    {{ t.modal.title }}
                </h3>
                <p class="mb-6 text-sm text-gray-500">{{ planSubtitle }}</p>

                <form class="space-y-4" @submit.prevent="submitPlanRequest">
                    <input type="hidden" v-model="planForm.plan_name" />
                    <input type="hidden" v-model="planForm.plan_price" />

                    <div>
                        <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            {{ t.modal.fullName }}
                        </FormLabel>
                        <FormInput
                            v-model="planForm.full_name"
                            :placeholder="t.modal.placeholders.name"
                            class="mt-1 w-full bg-gray-50 p-3 text-sm text-gray-900 focus:border-[#08459f]"
                        />
                        <InputError class="mt-2" :message="planForm.errors.full_name" />
                    </div>
                    <div>
                        <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            {{ t.modal.email }}
                        </FormLabel>
                        <FormInput
                            v-model="planForm.email"
                            type="email"
                            placeholder="email@company.com"
                            class="mt-1 w-full bg-gray-50 p-3 text-sm text-gray-900 focus:border-[#08459f]"
                        />
                        <InputError class="mt-2" :message="planForm.errors.email" />
                    </div>
                    <div>
                        <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            {{ t.modal.company }}
                        </FormLabel>
                        <FormInput
                            v-model="planForm.company"
                            :placeholder="t.modal.placeholders.company"
                            class="mt-1 w-full bg-gray-50 p-3 text-sm text-gray-900 focus:border-[#08459f]"
                        />
                        <InputError class="mt-2" :message="planForm.errors.company" />
                    </div>
                    <div>
                        <FormLabel class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            {{ t.modal.phone }}
                        </FormLabel>
                        <FormInput
                            v-model="planForm.phone"
                            type="tel"
                            placeholder="(000) 000-0000"
                            class="mt-1 w-full bg-gray-50 p-3 text-sm text-gray-900 focus:border-[#08459f]"
                        />
                        <InputError class="mt-2" :message="planForm.errors.phone" />
                    </div>
                    <button
                        type="submit"
                        class="btn-brand w-full py-4 text-xs font-bold uppercase tracking-[0.3em] text-white"
                        :disabled="planForm.processing"
                    >
                        {{ planForm.processing ? t.misc.sending : t.modal.submit }}
                    </button>
                </form>
            </div>
        </div>

        <footer class="bg-black py-12 text-center">
            <p class="text-[9px] font-bold uppercase tracking-[0.4em] text-gray-300">
                EFCTS LLC | {{ contact.address }} | {{ contact.phone }}
            </p>
        </footer>
    </div>
</template>

<style scoped>
:root {
    --brand-blue: #08459f;
}

.video-overlay {
    background: linear-gradient(to bottom, rgba(5, 5, 5, 0.4) 0%, rgba(5, 5, 5, 0.2) 50%, rgba(5, 5, 5, 0.8) 100%);
}

.nav-link-new {
    position: relative;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.nav-link-new:hover {
    opacity: 1;
    color: var(--brand-blue);
}

.btn-brand {
    background-color: var(--brand-blue);
    transition: all 0.3s ease;
}

.btn-brand:hover {
    background-color: #06367a;
    transform: translateY(-2px);
}

.text-brand {
    color: var(--brand-blue);
}

:deep(.text-brand) {
    color: var(--brand-blue);
}

.bg-brand {
    background-color: var(--brand-blue);
}

.border-brand {
    border-color: var(--brand-blue);
}

.mobile-menu-panel {
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.mobile-menu-panel.active {
    transform: translateX(0);
}

.mobile-link {
    font-size: 0.875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgb(255 255 255 / 0.7);
    transition: color 0.2s ease;
}

.mobile-link:hover {
    color: white;
}

.fade-up {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.fade-up.visible {
    opacity: 1;
    transform: translateY(0);
}

.active-tab {
    color: var(--brand-blue);
    opacity: 1 !important;
    border-bottom: 2px solid var(--brand-blue);
    padding-bottom: 16px;
}
</style>
