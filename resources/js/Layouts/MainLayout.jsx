import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

// ── SVG Icon Components ──────────────────────────────────────────────
const Icons = {
    Home: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" />
            <path d="M9 21V12h6v9" />
        </svg>
    ),
    ChartBar: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="3" y="12" width="4" height="9" rx="1" />
            <rect x="10" y="7" width="4" height="14" rx="1" />
            <rect x="17" y="3" width="4" height="18" rx="1" />
        </svg>
    ),
    Bolt: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
    ),
    CreditCard: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <line x1="2" y1="10" x2="22" y2="10" />
        </svg>
    ),
    Building: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18" />
            <path d="M2 22h20" />
            <path d="M10 6h4" /><path d="M10 10h4" /><path d="M10 14h4" />
        </svg>
    ),
    Clipboard: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2" />
            <rect x="8" y="2" width="8" height="4" rx="1" />
            <path d="M9 12h6" /><path d="M9 16h6" />
        </svg>
    ),
    Document: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="15" y2="17" />
        </svg>
    ),
    Cog: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
        </svg>
    ),
    Logout: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
        </svg>
    ),
    ChevronDown: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <polyline points="6 9 12 15 18 9" />
        </svg>
    ),
    ChevronRight: (props) => (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
            <polyline points="9 18 15 12 9 6" />
        </svg>
    ),
};

// ── Navigation Data ──────────────────────────────────────────────────
const navigationItems = [
    { name: 'Dashboard', href: '/', icon: Icons.Home, description: 'Overview & analytics' },
    {
        name: 'Monitoring',
        icon: Icons.ChartBar,
        isDropdown: true,
        description: 'Data monitoring',
        items: [
            { name: 'BFKO', href: '/bfko', icon: Icons.Bolt, description: 'Payroll deductions', color: '#F5C842' },
            { name: 'CC Card', href: '/cc-card', icon: Icons.CreditCard, description: 'Corporate cards', color: '#4AADE8' },
            { name: 'Service Fee', href: '/service-fee', icon: Icons.Building, description: 'Hotel & Flight', color: '#34D399' },
            { name: 'SPPD', href: '/sppd', icon: Icons.Clipboard, description: 'Travel assignments', color: '#E8636B' },
        ]
    },
    { name: 'Documents', href: '/documents', icon: Icons.Document, description: 'File management' },
];

// ── NavItem Component ────────────────────────────────────────────────
function NavItem({ item, isActive, index }) {
    return (
        <Link
            href={item.href}
            className={`group relative flex items-center gap-3
                px-3 py-2.5 rounded-xl cursor-pointer
                transition-all duration-300 ease-out
                ${isActive
                    ? 'bg-gradient-to-r from-[#F5C842] to-[#F7D56B] text-[#1a4a6b] shadow-lg shadow-amber-400/20'
                    : 'text-white/75 hover:text-white hover:bg-white/[0.08]'
                }`}
            style={{ animation: `slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) ${index * 60}ms both` }}
        >
            {/* Active indicator bar */}
            <div className={`absolute left-0 top-1/2 -translate-y-1/2 w-[3px] rounded-r-full transition-all duration-300
                ${isActive ? 'h-7 bg-[#F5C842] shadow-[0_0_10px_rgba(245,200,66,0.5)]' : 'h-0 bg-transparent'}`}
            />

            {/* Icon */}
            <div className={`flex items-center justify-center w-9 h-9 rounded-lg transition-all duration-300
                ${isActive
                    ? 'bg-white/30'
                    : 'bg-white/[0.06] group-hover:bg-white/[0.1]'
                }`}>
                <item.icon className="w-[18px] h-[18px]" />
            </div>

            {/* Label */}
            <div className="flex-1 min-w-0">
                <span className="text-[13px] font-semibold block truncate font-display">{item.name}</span>
                <span className={`text-[10px] block truncate transition-colors duration-300
                    ${isActive ? 'text-[#1a4a6b]/60' : 'text-white/40 group-hover:text-white/55'}`}>
                    {item.description}
                </span>
            </div>

            {/* Active dot */}
            {isActive && (
                <div className="w-1.5 h-1.5 rounded-full bg-[#1a4a6b]/50 animate-pulse-glow" />
            )}

            {/* Hover arrow */}
            {!isActive && (
                <Icons.ChevronRight className="w-3.5 h-3.5 opacity-0 -translate-x-1 group-hover:opacity-60 group-hover:translate-x-0 transition-all duration-200" />
            )}
        </Link>
    );
}

// ── DropdownNavItem Component ────────────────────────────────────────
function DropdownNavItem({ item, url, isOpen, onToggle, index }) {
    const isAnyChildActive = item.items.some(child =>
        url === child.href || (child.href !== '/' && url.startsWith(child.href))
    );

    return (
        <div style={{ animation: `slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) ${index * 60}ms both` }}>
            {/* Toggle */}
            <button
                onClick={onToggle}
                className={`group w-full flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer
                    transition-all duration-300 ease-out
                    ${isAnyChildActive
                        ? 'bg-gradient-to-r from-[#F5C842] to-[#F7D56B] text-[#1a4a6b] shadow-lg shadow-amber-400/20'
                        : 'text-white/75 hover:text-white hover:bg-white/[0.08]'
                    }`}
            >
                {/* Active indicator */}
                <div className={`absolute left-0 top-1/2 -translate-y-1/2 w-[3px] rounded-r-full transition-all duration-300
                    ${isAnyChildActive ? 'h-7 bg-[#F5C842] shadow-[0_0_10px_rgba(245,200,66,0.5)]' : 'h-0 bg-transparent'}`}
                />

                <div className={`flex items-center justify-center w-9 h-9 rounded-lg transition-all duration-300
                    ${isAnyChildActive
                        ? 'bg-white/30'
                        : 'bg-white/[0.06] group-hover:bg-white/[0.1]'
                    }`}>
                    <item.icon className="w-[18px] h-[18px]" />
                </div>

                <div className="flex-1 text-left min-w-0">
                    <span className="text-[13px] font-semibold block truncate font-display">{item.name}</span>
                    <span className={`text-[10px] block truncate transition-colors
                        ${isAnyChildActive ? 'text-[#1a4a6b]/60' : 'text-white/40'}`}>
                        {item.description}
                    </span>
                </div>

                <Icons.ChevronDown className={`w-4 h-4 transition-transform duration-300 flex-shrink-0
                    ${isOpen ? 'rotate-180' : ''}`} />
            </button>

            {/* Dropdown children */}
            <div className={`dropdown-content overflow-hidden
                ${isOpen ? 'max-h-96 opacity-100 mt-1.5' : 'max-h-0 opacity-0'}`}>
                <div className="ml-4 pl-3 space-y-0.5 border-l border-white/[0.08]">
                    {item.items.map((child, childIdx) => {
                        const isChildActive = url === child.href || (child.href !== '/' && url.startsWith(child.href));
                        return (
                            <Link
                                key={child.name}
                                href={child.href}
                                className={`group relative flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer
                                    transition-all duration-250 ease-out
                                    ${isChildActive
                                        ? 'bg-white/[0.12] text-white shadow-sm'
                                        : 'text-white/55 hover:text-white/85 hover:bg-white/[0.05]'
                                    }`}
                            >
                                {/* Colored dot indicator */}
                                <div className={`w-2 h-2 rounded-full transition-all duration-300 flex-shrink-0
                                    ${isChildActive
                                        ? 'scale-110 shadow-[0_0_8px_var(--dot-color)]'
                                        : 'scale-75 opacity-60 group-hover:scale-100 group-hover:opacity-100'
                                    }`}
                                    style={{ backgroundColor: child.color, '--dot-color': child.color + '80' }}
                                />

                                <div className={`flex items-center justify-center w-7 h-7 rounded-md transition-all duration-300
                                    ${isChildActive
                                        ? 'bg-white/[0.12]'
                                        : 'bg-transparent group-hover:bg-white/[0.06]'
                                    }`}>
                                    <child.icon className="w-[15px] h-[15px]" />
                                </div>

                                <div className="flex-1 min-w-0">
                                    <span className="text-[12px] font-semibold block truncate font-display">{child.name}</span>
                                    <span className={`text-[9px] block truncate
                                        ${isChildActive ? 'text-white/50' : 'text-white/30'}`}>
                                        {child.description}
                                    </span>
                                </div>

                                {isChildActive && (
                                    <div className="w-1 h-1 rounded-full bg-white/70" />
                                )}
                            </Link>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

// ── MainLayout Component ─────────────────────────────────────────────
export default function MainLayout({ children }) {
    const { url } = usePage();
    const [openDropdown, setOpenDropdown] = useState(null);
    const [mounted, setMounted] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);

    // Mount animation trigger
    useEffect(() => {
        const t = setTimeout(() => setMounted(true), 50);
        return () => clearTimeout(t);
    }, []);

    // Auto-expand dropdown if on a monitoring page
    useEffect(() => {
        const monitoringRoutes = ['/bfko', '/cc-card', '/service-fee', '/sppd'];
        if (monitoringRoutes.some(r => url === r || url.startsWith(r + '/'))) {
            setOpenDropdown('monitoring');
        }
    }, [url]);

    // Close sidebar on route change (mobile)
    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    return (
        <div className="flex h-screen bg-gradient-to-br from-slate-50 via-blue-50/40 to-sky-50/30 font-sans">
            {/* ─── Mobile Hamburger Button ─── */}
            <button
                onClick={() => setSidebarOpen(!sidebarOpen)}
                className="lg:hidden fixed top-4 left-4 z-50 p-2 bg-[#1a3f5c] text-white rounded-xl shadow-lg hover:bg-[#1d4b6d] transition-all"
                aria-label="Toggle sidebar"
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {sidebarOpen ? (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    ) : (
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                    )}
                </svg>
            </button>

            {/* ─── Mobile Backdrop ─── */}
            {sidebarOpen && (
                <div
                    className="lg:hidden fixed inset-0 bg-black/50 z-30 backdrop-blur-sm"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* ─── Sidebar ─── */}
            <aside className={`w-[260px] flex-shrink-0 flex flex-col relative overflow-hidden
                bg-gradient-to-b from-[#1a3f5c] via-[#1d4b6d] to-[#163a55]
                transition-all duration-500 ease-out
                fixed lg:relative inset-y-0 left-0 z-40
                ${sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
                ${mounted ? 'opacity-100' : 'opacity-0'}`}
            >
                {/* Decorative ambient orbs */}
                <div className="absolute top-[-60px] right-[-40px] w-[180px] h-[180px] bg-[#4AADE8]/20 rounded-full blur-[80px] pointer-events-none" />
                <div className="absolute bottom-[-40px] left-[-30px] w-[140px] h-[140px] bg-[#F5C842]/10 rounded-full blur-[60px] pointer-events-none" />
                <div className="absolute top-1/2 right-0 w-[100px] h-[200px] bg-[#34D399]/5 rounded-full blur-[50px] pointer-events-none" />

                {/* Noise overlay for texture */}
                <div className="noise-overlay absolute inset-0 pointer-events-none" />

                {/* ── Brand Header ── */}
                <div className="relative px-5 py-5 border-b border-white/[0.07]">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg shadow-black/10 ring-1 ring-white/20">
                            <img src="/images/Logo_PLN.png" alt="PLN Logo" className="w-7 h-7 object-contain" />
                        </div>
                        <div>
                            <h1 className="text-[15px] font-extrabold text-white tracking-tight font-display">
                                PLN Dashboard
                            </h1>
                            <p className="text-[10px] text-white/35 font-medium tracking-wider uppercase">
                                Monitoring System
                            </p>
                        </div>
                    </div>
                </div>

                {/* ── User Profile ── */}
                <div className="relative px-5 py-4 border-b border-white/[0.07]">
                    <div className="flex items-center gap-3">
                        <div className="relative">
                            <div className="w-10 h-10 bg-gradient-to-br from-[#F5C842] to-[#E5B82E] rounded-full flex items-center justify-center shadow-md ring-2 ring-[#F5C842]/20">
                                <svg className="w-5 h-5 text-[#1a3f5c]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                </svg>
                            </div>
                            {/* Online status */}
                            <div className="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-400 rounded-full border-2 border-[#1d4b6d]" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-[13px] font-semibold text-white truncate font-display">User</p>
                            <p className="text-[10px] text-white/35 truncate">User@gmail.com</p>
                        </div>
                    </div>
                </div>

                {/* ── Section Label ── */}
                <div className="px-5 pt-5 pb-2">
                    <p className="text-[9px] font-bold text-white/25 uppercase tracking-[0.15em] font-display">
                        Navigation
                    </p>
                </div>

                {/* ── Nav Items ── */}
                <nav className="relative flex-1 px-3 space-y-1 overflow-y-auto sidebar-scroll pb-4">
                    {navigationItems.map((item, index) => {
                        if (item.isDropdown) {
                            return (
                                <DropdownNavItem
                                    key={item.name}
                                    item={item}
                                    url={url}
                                    isOpen={openDropdown === 'monitoring'}
                                    onToggle={() => setOpenDropdown(openDropdown === 'monitoring' ? null : 'monitoring')}
                                    index={index}
                                />
                            );
                        }
                        const isActive = url === item.href || (item.href !== '/' && url.startsWith(item.href));
                        return (
                            <NavItem
                                key={item.name}
                                item={item}
                                isActive={isActive}
                                index={index}
                            />
                        );
                    })}
                </nav>

                {/* ── Section Label ── */}
                <div className="px-5 pt-2 pb-2">
                    <p className="text-[9px] font-bold text-white/25 uppercase tracking-[0.15em] font-display">
                        System
                    </p>
                </div>

                {/* ── Bottom Nav ── */}
                <div className="relative px-3 pb-4 space-y-1 border-t border-white/[0.07] pt-2">
                    <Link
                        href="/settings"
                        className="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/55
                            hover:text-white/85 hover:bg-white/[0.06] transition-all duration-300 cursor-pointer"
                    >
                        <div className="flex items-center justify-center w-9 h-9 rounded-lg bg-white/[0.04] group-hover:bg-white/[0.08] transition-all">
                            <Icons.Cog className="w-[18px] h-[18px] group-hover:rotate-45 transition-transform duration-500" />
                        </div>
                        <span className="text-[13px] font-semibold font-display">Settings</span>
                    </Link>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/55 w-full
                            hover:text-white hover:bg-[#E8636B]/20 transition-all duration-300 cursor-pointer"
                    >
                        <div className="flex items-center justify-center w-9 h-9 rounded-lg bg-white/[0.04] group-hover:bg-[#E8636B]/20 transition-all">
                            <Icons.Logout className="w-[18px] h-[18px]" />
                        </div>
                        <span className="text-[13px] font-semibold font-display">Log Out</span>
                    </Link>
                </div>
            </aside>

            {/* ─── Main Content ─── */}
            <main className="flex-1 overflow-auto min-w-0">
                {children}
            </main>
        </div>
    );
}