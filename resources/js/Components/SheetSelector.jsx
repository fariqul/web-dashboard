import React, { useState, useMemo, useRef, useEffect } from 'react';

export default function SheetSelector({ sheets = [], selectedSheet, onChange, className = '' }) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [expandedYears, setExpandedYears] = useState(new Set());
    const dropdownRef = useRef(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
                setSearchQuery('');
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Toggle year expansion
    const toggleYear = (year) => {
        setExpandedYears(prev => {
            const newSet = new Set(prev);
            if (newSet.has(year)) {
                newSet.delete(year);
            } else {
                newSet.add(year);
            }
            return newSet;
        });
    };

    // Group sheets by year
    const groupedSheets = useMemo(() => {
        if (!sheets || sheets.length === 0) return {};
        
        // Month name mapping for sorting (Indonesian and English)
        const monthOrder = {
            'januari': 1, 'january': 1, 'jan': 1,
            'februari': 2, 'february': 2, 'feb': 2,
            'maret': 3, 'march': 3, 'mar': 3,
            'april': 4, 'apr': 4,
            'mei': 5, 'may': 5,
            'juni': 6, 'june': 6, 'jun': 6,
            'juli': 7, 'july': 7, 'jul': 7,
            'agustus': 8, 'august': 8, 'agt': 8, 'aug': 8,
            'september': 9, 'sept': 9, 'sep': 9,
            'oktober': 10, 'october': 10, 'okt': 10, 'oct': 10,
            'november': 11, 'nov': 11,
            'desember': 12, 'december': 12, 'des': 12, 'dec': 12
        };
        
        const getMonthNumber = (sheetName) => {
            const lower = sheetName.toLowerCase();
            for (const [monthName, order] of Object.entries(monthOrder)) {
                if (lower.includes(monthName)) {
                    return order;
                }
            }
            return 99; // Unknown months go to the end
        };
        
        const groups = {};
        sheets.forEach(sheet => {
            // Extract year from sheet name (assuming format like "Juli 2025", "Agustus 2025", etc)
            const yearMatch = sheet.match(/\d{4}/);
            const year = yearMatch ? yearMatch[0] : 'Other';
            
            if (!groups[year]) {
                groups[year] = [];
            }
            groups[year].push(sheet);
        });
        
        // Sort years in descending order (newest first) and months in chronological order
        const sortedGroups = {};
        Object.keys(groups)
            .sort((a, b) => b.localeCompare(a))
            .forEach(year => {
                sortedGroups[year] = groups[year].sort((a, b) => {
                    const monthA = getMonthNumber(a);
                    const monthB = getMonthNumber(b);
                    return monthA - monthB;
                });
            });
        
        return sortedGroups;
    }, [sheets]);

    // Filter sheets based on search query
    const filteredGroupedSheets = useMemo(() => {
        if (!searchQuery) return groupedSheets;
        
        const filtered = {};
        Object.entries(groupedSheets).forEach(([year, sheetList]) => {
            const matchingSheets = sheetList.filter(sheet => 
                sheet.toLowerCase().includes(searchQuery.toLowerCase())
            );
            if (matchingSheets.length > 0) {
                filtered[year] = matchingSheets;
            }
        });
        return filtered;
    }, [groupedSheets, searchQuery]);

    // Auto-expand years when searching
    useEffect(() => {
        if (searchQuery) {
            // Expand all years that have matching results
            const yearsWithResults = Object.keys(filteredGroupedSheets);
            setExpandedYears(new Set(yearsWithResults));
        }
    }, [searchQuery, filteredGroupedSheets]);

    const handleSelect = (sheet) => {
        onChange(sheet);
        setIsOpen(false);
        setSearchQuery('');
    };

    const displayValue = selectedSheet === 'all' ? 'All Months' : selectedSheet;

    return (
        <div className={`relative ${className}`} ref={dropdownRef}>
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full appearance-none pl-4 pr-10 py-2 bg-cyan-200 text-gray-800 rounded-lg hover:bg-cyan-300 transition cursor-pointer focus:ring-2 focus:ring-cyan-400 text-left flex items-center justify-between"
            >
                <span className="truncate">{displayValue}</span>
                <svg 
                    className={`w-5 h-5 text-gray-700 transition-transform flex-shrink-0 ${isOpen ? 'rotate-180' : ''}`} 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {/* Dropdown Content */}
            {isOpen && (
                <div className="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-xl max-h-96 overflow-hidden">
                    {/* Search Box */}
                    <div className="p-2 border-b sticky top-0 bg-white z-10">
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="Search month/year..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                                onClick={(e) => e.stopPropagation()}
                                autoFocus
                            />
                            <svg className="w-4 h-4 absolute left-2.5 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Grouped Options */}
                    <div className="overflow-y-auto max-h-80">
                        {/* All Months Option */}
                        <button
                            type="button"
                            onClick={() => handleSelect('all')}
                            className={`w-full text-left px-4 py-2.5 text-sm hover:bg-cyan-50 transition border-b ${
                                selectedSheet === 'all' ? 'bg-cyan-100 text-cyan-900 font-semibold' : 'text-gray-700'
                            }`}
                        >
                            <span className="flex items-center justify-between">
                                <span className="flex items-center gap-2">
                                    <span className="text-lg">📊</span>
                                    <span>All Months</span>
                                </span>
                                {selectedSheet === 'all' && (
                                    <span className="text-cyan-600">✓</span>
                                )}
                            </span>
                        </button>

                        {/* No Results */}
                        {Object.keys(filteredGroupedSheets).length === 0 ? (
                            <div className="px-4 py-8 text-sm text-gray-500 text-center">
                                <svg className="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p className="font-medium">No sheets found</p>
                                <p className="text-xs text-gray-400 mt-1">Try different search terms</p>
                            </div>
                        ) : (
                            /* Grouped by Year - Collapsible */
                            Object.entries(filteredGroupedSheets).map(([year, sheetList]) => {
                                const isExpanded = expandedYears.has(year);
                                
                                return (
                                    <div key={year} className="border-b border-gray-100 last:border-0">
                                        {/* Year Header - Clickable to Expand/Collapse */}
                                        <button
                                            type="button"
                                            onClick={() => toggleYear(year)}
                                            className="w-full px-4 py-2.5 bg-gradient-to-r from-cyan-50 to-blue-50 hover:from-cyan-100 hover:to-blue-100 text-sm font-bold text-cyan-800 sticky top-0 flex items-center gap-2 transition-colors"
                                        >
                                            {/* Expand/Collapse Icon */}
                                            <svg 
                                                className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-90' : ''}`}
                                                fill="none" 
                                                stroke="currentColor" 
                                                viewBox="0 0 24 24"
                                            >
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                            </svg>
                                            
                                            {/* Calendar Icon */}
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            
                                            <span>{year}</span>
                                            
                                            {/* Count Badge */}
                                            <span className="ml-auto bg-cyan-600 text-white text-xs px-2.5 py-0.5 rounded-full font-semibold">
                                                {sheetList.length}
                                            </span>
                                        </button>
                                        
                                        {/* Collapsible Sheets List */}
                                        {isExpanded && (
                                            <div className="bg-white">
                                                {sheetList.map(sheet => (
                                                    <button
                                                        key={sheet}
                                                        type="button"
                                                        onClick={() => handleSelect(sheet)}
                                                        className={`w-full text-left px-4 py-2 text-sm hover:bg-cyan-50 transition border-l-2 ${
                                                            selectedSheet === sheet 
                                                                ? 'bg-cyan-100 text-cyan-900 font-semibold border-cyan-600' 
                                                                : 'text-gray-700 border-transparent hover:border-cyan-300'
                                                        }`}
                                                    >
                                                        <span className="flex items-center justify-between">
                                                            <span className="pl-8">{sheet}</span>
                                                            {selectedSheet === sheet && (
                                                                <span className="text-cyan-600 font-bold">✓</span>
                                                            )}
                                                        </span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                );
                            })
                        )}
                    </div>

                    {/* Footer Info */}
                    <div className="px-4 py-2 bg-gray-50 border-t text-xs text-gray-600 text-center">
                        {sheets.length} sheet{sheets.length !== 1 ? 's' : ''} available
                    </div>
                </div>
            )}
        </div>
    );
}
