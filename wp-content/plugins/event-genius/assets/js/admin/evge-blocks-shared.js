// Common utilities and functions for blocks
window.evgeBlocksShared = {
    // Utility function to format dates
    formatDate: function(date) {
        return new Date(date).toLocaleDateString();
    },

    // Common API request handler
    apiRequest: async function(path, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        try {
            const response = await fetch(path, { ...defaultOptions, ...options });
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.json();
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    },

    // Common loading state handler
    setLoading: function(elementId, isLoading) {
        const element = document.getElementById(elementId);
        if (element) {
            element.classList.toggle('is-loading', isLoading);
        }
    }
}; 