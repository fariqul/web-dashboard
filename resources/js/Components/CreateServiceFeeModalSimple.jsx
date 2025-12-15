import React, { useState } from 'react';

export default function CreateServiceFeeModalSimple({ isOpen, onClose }) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4">
                {/* Overlay */}
                <div 
                    className="fixed inset-0 bg-gray-500 bg-opacity-75" 
                    onClick={onClose}
                ></div>

                {/* Modal */}
                <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                    <h2 className="text-xl font-bold mb-4">Add Service Fee Data</h2>
                    <p className="text-gray-600 mb-4">
                        Modal is working! This is a test version.
                    </p>
                    <div className="flex justify-end gap-3">
                        <button
                            onClick={onClose}
                            className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                        >
                            Close
                        </button>
                        <button
                            className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
