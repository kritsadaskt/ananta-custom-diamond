import { createRoot, useState, useEffect } from '@wordpress/element';

const DiamondSelector = () => {
    const [diamonds, setDiamonds] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedDiamond, setSelectedDiamond] = useState(null);

    useEffect(() => {
        fetchDiamonds();
    }, []);

    const fetchDiamonds = async () => {
        try {
            const response = await fetch('/wp-json/ananta-custom-diamond/v1/diamonds');
            if (!response.ok) throw new Error('Failed to fetch diamonds');
            const data = await response.json();
            setDiamonds(data);
            setLoading(false);
        } catch (err) {
            setError(err.message);
            setLoading(false);
        }
    };

    if (loading) return <div>Loading diamonds...</div>;
    if (error) return <div>Error: {error}</div>;

    return (
        <div className="ananta-diamond-selector">
            <h4>Choose your own diamond (required):</h4>
            <select 
                className="diamond-dropdown"
                value={selectedDiamond?.id || ''}
                onChange={(e) => {
                    const diamond = diamonds.find(d => d.id === parseInt(e.target.value));
                    setSelectedDiamond(diamond);
                }}
            >
                <option value="">Select a diamond</option>
                {diamonds.map((diamond) => (
                    <option key={diamond.id} value={diamond.id}>
                        {diamond.shape} Diamond - {diamond.size}ct, {diamond.color}, {diamond.clarity} - ${diamond.price_usd}
                    </option>
                ))}
            </select>
            <style>{`
                .diamond-list {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                .diamond-item {
                    border: 1px solid #ddd;
                    padding: 15px;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .diamond-item:hover {
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .diamond-item.selected {
                    border-color: #0073aa;
                    background-color: #f0f6fc;
                }
            `}</style>
        </div>
    );
};

// Ensure the mount point exists before trying to render
document.addEventListener('DOMContentLoaded', () => {
    const mountPoint = document.getElementById('ananta_custom_diamond_selector');
    if (mountPoint) {
        const root = createRoot(mountPoint);
        root.render(<DiamondSelector />);
    }
});