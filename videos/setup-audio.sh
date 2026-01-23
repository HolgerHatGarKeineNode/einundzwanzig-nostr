#!/bin/bash

# Setup Audio Folders for NIP-05 Tutorial Video
# This script creates the required folder structure for audio files

echo "🎵 Setting up audio folders..."

# Create main folders
mkdir -p public/music
mkdir -p public/sfx

echo "✅ Created folders:"
echo "   - public/music/"
echo "   - public/sfx/"

# Create placeholder README files
cat > public/music/README.md << EOF
# Background Music

Place your background music file here:
- \`background-music.mp3\`

Recommended: 40-60 seconds, loop-friendly, tech/crypto theme

See AUDIO_GUIDE.md for download sources and tips.
EOF

cat > public/sfx/README.md << EOF
# Sound Effects

Place the following sound effect files here:

## Required Files:
- logo-whoosh.mp3
- logo-reveal.mp3
- card-slide.mp3
- ui-appear.mp3
- typing.mp3
- slide-in.mp3
- button-hover.mp3
- button-click.mp3
- success-chime.mp3
- success-fanfare.mp3
- badge-appear.mp3
- checkmark-pop.mp3
- outro-entrance.mp3
- url-emphasis.mp3
- final-chime.mp3

See AUDIO_GUIDE.md for detailed descriptions and download links.
EOF

echo ""
echo "📝 Created README files in both folders"
echo ""
echo "🎯 Next steps:"
echo "1. Download audio files from Pixabay or Freesound (see AUDIO_GUIDE.md)"
echo "2. Place files in public/music/ and public/sfx/"
echo "3. Run 'npm run dev' to preview with audio"
echo ""
echo "✨ Setup complete!"
