import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

// ── SVG Icons ────────────────────────────────────────────────────────
const FolderIcon = (props) => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" />
    </svg>
);

const FileIcon = (props) => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" />
        <polyline points="14 2 14 8 20 8" />
        <line x1="9" y1="13" x2="15" y2="13" />
        <line x1="9" y1="17" x2="15" y2="17" />
    </svg>
);

const SearchIcon = (props) => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
        <circle cx="11" cy="11" r="8" />
        <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
);

const GridIcon = (props) => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
        <rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" />
        <rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />
    </svg>
);

const ListIcon = (props) => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" {...props}>
        <line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" />
        <line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" />
    </svg>
);

export default function Documents() {
    const [searchQuery, setSearchQuery] = useState('');
    const [viewMode, setViewMode] = useState('grid');

    const folders = [
        { id: 1, name: 'BFKO Reports', items: 24, color: '#F5C842' },
        { id: 2, name: 'CC Card Data', items: 18, color: '#4AADE8' },
        { id: 3, name: 'Service Fee', items: 12, color: '#34D399' },
        { id: 4, name: 'SPPD Files', items: 31, color: '#E8636B' },
        { id: 5, name: 'Monthly Reports', items: 7, color: '#818CF8' },
        { id: 6, name: 'Annual Summary', items: 3, color: '#F472B6' },
        { id: 7, name: 'Templates', items: 5, color: '#FB923C' },
        { id: 8, name: 'Archives', items: 42, color: '#94A3B8' },
    ];

    const files = [
        { id: 1, name: 'BFKO Report January 2025', type: 'PDF', size: '2.4 MB', date: '2025-01-15', color: '#E8636B' },
        { id: 2, name: 'CC Card Summary Q4', type: 'XLSX', size: '1.8 MB', date: '2025-01-10', color: '#22C55E' },
        { id: 3, name: 'Service Fee Reconciliation', type: 'PDF', size: '3.1 MB', date: '2025-01-08', color: '#E8636B' },
        { id: 4, name: 'SPPD Travel Analysis', type: 'DOCX', size: '856 KB', date: '2025-01-05', color: '#4AADE8' },
    ];

    const getTypeColor = (type) => {
        switch (type) {
            case 'PDF': return { bg: 'bg-red-50', text: 'text-red-600', border: 'border-red-200' };
            case 'XLSX': return { bg: 'bg-emerald-50', text: 'text-emerald-600', border: 'border-emerald-200' };
            case 'DOCX': return { bg: 'bg-sky-50', text: 'text-sky-600', border: 'border-sky-200' };
            default: return { bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200' };
        }
    };

    return (
        <MainLayout>
            <Head title="Documents" />
            <div className="p-6 lg:p-8 min-h-screen">
                {/* Header */}
                <div className="flex items-center justify-between mb-8"
                    style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both' }}>
                    <div>
                        <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight font-display">
                            Documents
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">Manage your files and reports</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setViewMode('grid')}
                            className={`p-2 rounded-lg transition-all cursor-pointer ${viewMode === 'grid' ? 'bg-[#4AADE8] text-white shadow-md' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                                }`}>
                            <GridIcon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => setViewMode('list')}
                            className={`p-2 rounded-lg transition-all cursor-pointer ${viewMode === 'list' ? 'bg-[#4AADE8] text-white shadow-md' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                                }`}>
                            <ListIcon className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {/* Search */}
                <div className="mb-8 max-w-lg"
                    style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 100ms both' }}>
                    <div className="relative">
                        <SearchIcon className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search files and folders..."
                            className="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl
                                text-sm focus:border-[#4AADE8] focus:ring-2 focus:ring-[#4AADE8]/20
                                transition-all shadow-sm placeholder:text-gray-400"
                        />
                    </div>
                </div>

                {/* Folders */}
                <div className="mb-8" style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 200ms both' }}>
                    <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 font-display">
                        Folders
                    </h2>
                    <div className={`grid gap-3 ${viewMode === 'grid'
                            ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4'
                            : 'grid-cols-1'
                        }`}>
                        {folders.map((folder, idx) => (
                            <div
                                key={folder.id}
                                className="group bg-white rounded-xl p-4 border border-gray-100
                                    hover:border-gray-200 hover:shadow-md transition-all duration-300
                                    cursor-pointer flex items-center gap-3 hover:-translate-y-0.5"
                                style={{ animation: `slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) ${250 + idx * 40}ms both` }}
                            >
                                <div className="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                    style={{ backgroundColor: folder.color + '18' }}>
                                    <FolderIcon className="w-5 h-5" style={{ color: folder.color }} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-semibold text-gray-800 truncate font-display">{folder.name}</p>
                                    <p className="text-[10px] text-gray-400 mt-0.5">{folder.items} items</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Files */}
                <div style={{ animation: 'slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 400ms both' }}>
                    <h2 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 font-display">
                        Recent Files
                    </h2>
                    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                        <div className="divide-y divide-gray-50">
                            {files.map((file, idx) => {
                                const typeColor = getTypeColor(file.type);
                                return (
                                    <div
                                        key={file.id}
                                        className="group flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/50 
                                            cursor-pointer transition-all duration-200"
                                        style={{ animation: `slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) ${450 + idx * 60}ms both` }}
                                    >
                                        <div className="w-9 h-9 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0
                                            group-hover:bg-gray-100 transition-all">
                                            <FileIcon className="w-4.5 h-4.5 text-gray-400" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-gray-800 truncate">{file.name}</p>
                                            <p className="text-[10px] text-gray-400 mt-0.5">{file.size} • {file.date}</p>
                                        </div>
                                        <span className={`px-2 py-0.5 rounded-md text-[9px] font-bold tracking-wide ${typeColor.bg} ${typeColor.text} border ${typeColor.border}`}>
                                            {file.type}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
