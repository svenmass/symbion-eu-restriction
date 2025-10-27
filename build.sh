#!/bin/bash
# Build-Script für Production Release

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Usage: ./build.sh [version]"
    echo "Example: ./build.sh 1.0.0"
    exit 1
fi

echo "🔨 Building Symbion EU Restriction v$VERSION..."

# Erstelle Build-Verzeichnis
rm -rf build
mkdir -p build/symbion-eu-restriction

# Kopiere Production-Dateien
echo "📦 Copying files..."
cp -r includes build/symbion-eu-restriction/
cp -r assets build/symbion-eu-restriction/
cp symbion-eu-restriction.php build/symbion-eu-restriction/
cp README.md build/symbion-eu-restriction/
cp QUICKSTART.md build/symbion-eu-restriction/

# Entferne Development-Dateien
echo "🧹 Cleaning up..."
rm -rf build/symbion-eu-restriction/assets/brand
find build/symbion-eu-restriction -name ".DS_Store" -delete
find build/symbion-eu-restriction -name "*.log" -delete

# Erstelle ZIP
echo "📦 Creating ZIP..."
cd build
zip -r "symbion-eu-restriction-${VERSION}.zip" symbion-eu-restriction -q

# Fertig
cd ..
echo "✅ Build complete: build/symbion-eu-restriction-${VERSION}.zip"
echo ""
echo "Next steps:"
echo "1. Test the ZIP locally"
echo "2. Create Git tag: git tag -a v${VERSION} -m 'Release ${VERSION}'"
echo "3. Push tag: git push origin v${VERSION}"
echo "4. GitHub Actions will create the release automatically"

