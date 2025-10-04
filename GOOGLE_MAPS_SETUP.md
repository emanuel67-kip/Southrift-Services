# Google Maps API Setup Guide

## Quick Setup Steps

### 1. Get Google Maps API Key
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable these APIs:
   - Maps JavaScript API
   - Geocoding API (if using address lookups)
   - Places API (if using place searches)
4. Go to "Credentials" → "Create Credentials" → "API Key"
5. Copy your API key

### 2. Configure the API Key
Choose one of these methods:

#### Method 1: Environment File (.env)
1. Copy `.env.example` to `.env`
2. Replace `your_actual_google_maps_api_key_here` with your real API key

#### Method 2: System Environment Variable
Set the environment variable: `GOOGLE_MAPS_API_KEY=your_api_key_here`

#### Method 3: Direct Code Update
Edit `view_driver_location.php` and replace the demo key with your real key.

### 3. API Key Restrictions (Recommended)
1. In Google Cloud Console, click on your API key
2. Under "Application restrictions":
   - Choose "HTTP referrers (web sites)"
   - Add your domain: `yourdomain.com/*` or `localhost/*` for testing
3. Under "API restrictions":
   - Choose "Restrict key"
   - Select only the APIs you need

### 4. Test the Setup
1. Open the driver location page in your browser
2. Check browser console for any API er
rors
3. Verify that the map loads correctly

## Troubleshooting

### Common Errors:
- **InvalidKeyMapError**: API key is invalid or not set
- **RefererNotAllowedMapError**: Domain not added to allowed referrers
- **RequestDeniedMapError**: API not enabled for your project

### Demo Key Notice:
The system uses a demo API key by default. This key has limited usage and may not work in production. Please replace it with your own key.

## Cost Information
- Google Maps API has a generous free tier (28,000+ map loads per month)
- After free tier, pricing starts at $7 per 1,000 additional requests
- Set up billing alerts to monitor usage

## Security Best Practices
1. Always restrict your API key to specific domains/IPs
2. Only enable the APIs you actually use
3. Monitor usage regularly in Google Cloud Console
4. Never commit API keys to public repositories