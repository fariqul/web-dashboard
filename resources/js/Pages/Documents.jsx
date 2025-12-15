import React from 'react';
import MainLayout from '../Layouts/MainLayout';

export default function Documents() {
    const folders = [
        { id: 1, name: 'File 1' },
        { id: 2, name: 'File 1' },
        { id: 3, name: 'File 1' },
        { id: 4, name: 'File 1' },
        { id: 5, name: 'File 1' },
        { id: 6, name: 'File 1' },
        { id: 7, name: 'File 1' },
        { id: 8, name: 'File 1' },
        { id: 9, name: 'File 1' },
        { id: 10, name: 'File 1' },
        { id: 11, name: 'File 1' },
        { id: 12, name: 'File 1' },
    ];

    const files = [
        { id: 1, name: 'File BFKO januari' },
        { id: 2, name: 'File BFKO januari' },
        { id: 3, name: 'File BFKO januari' },
        { id: 4, name: 'File BFKO januari' },
    ];

    return (
        <MainLayout>
            <div className="p-8">
                {/* Header */}
                <h1 className="text-3xl font-bold mb-6">Documents</h1>

                {/* Search Bar */}
                <div className="mb-8 max-w-md">
                    <div className="relative">
                        <input
                            type="text"
                            placeholder="Search"
                            className="w-full px-4 py-2 pl-10 bg-cyan-100 border-0 rounded-lg focus:ring-2 focus:ring-cyan-300"
                        />
                        <svg className="w-5 h-5 absolute left-3 top-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                {/* Your Folders */}
                <div className="mb-8">
                    <h2 className="text-lg font-semibold mb-4">Your Folders</h2>
                    <div className="grid grid-cols-4 gap-4">
                        {folders.map((folder) => (
                            <div
                                key={folder.id}
                                className="bg-white rounded-lg p-6 hover:shadow-md transition cursor-pointer flex items-center gap-3"
                            >
                                {/* Folder Icon */}
                                <svg className="w-10 h-10 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                </svg>
                                <span className="text-gray-900 font-medium">{folder.name}</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Your Files */}
                <div>
                    <h2 className="text-lg font-semibold mb-4">Your Files</h2>
                    <div className="grid grid-cols-4 gap-4">
                        {files.map((file) => (
                            <div
                                key={file.id}
                                className="bg-white rounded-lg p-6 hover:shadow-md transition cursor-pointer flex flex-col items-center"
                            >
                                {/* PDF Icon */}
                                <svg className="w-20 h-20 mb-3" viewBox="0 0 64 64" fill="none">
                                    {/* Document Background */}
                                    <rect x="12" y="8" width="40" height="48" rx="2" fill="#E5E7EB" />
                                    {/* Folded Corner */}
                                    <path d="M42 8 L52 18 L42 18 Z" fill="#D1D5DB" />
                                    {/* PDF Badge */}
                                    <rect x="16" y="28" width="32" height="16" rx="2" fill="#0EA5E9" />
                                    <text x="32" y="39" fontSize="8" fontWeight="bold" fill="white" textAnchor="middle">PDF</text>
                                </svg>
                                <div className="flex items-center gap-2 text-sm text-gray-600">
                                    <svg className="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clipRule="evenodd" />
                                    </svg>
                                    <span>{file.name}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
