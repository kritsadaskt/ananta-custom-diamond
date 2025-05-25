import { createRoot, useState, useEffect } from '@wordpress/element';
import './style.scss';

const DiamondSelector = () => {
    const [diamonds, setDiamonds] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedDiamond, setSelectedDiamond] = useState(null);

    useEffect(() => {
        fetchDiamonds();
    }, []);

    useEffect(() => {
        selectedDiamond && console.log('selectedDiamond', selectedDiamond);
    }, [selectedDiamond]);

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
                    const diamond = diamonds.find(d => parseInt(d.id) === parseInt(e.target.value));
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