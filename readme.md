## E-Scooters

### URL
https://sbanasiak.github.io/escooters/public/index.html

### Changes made:
- Updated PHP, packages, and Docker Compose
- Updated Mapbox service with additional features:
  - normalizeCountryName()
  - getPlaceFromCoordinates()
  - Improved the existing setCoordinatesToCities() to be more precise
  - Improved the current country name normalizer so that if a country is not listed, the geocoder will be used

  Currently made it a singleton, which in the future will be injected or otherwise made available for other classes.

### Updated providers:
- **Fixed**: Bird, Dott, Link, Tier
- **Added**: BIT Mobility, Hulaj

### Plans for the application:
#### My reflections
By design, the site should remain a relatively simple aggregator of scooter data from various companies. After checking the options, for now, I don't see much possibility to retrieve the position of nearby scooters data in real time - most providers don't provide an public API or you have to access to it in a very "clumsy" way (e.g., generate magic links, tokens for a regular user, regularly over-generate them), which seems unrealistic for the large number of providers that such an application should support. My assumption for the application is a simple site that works fast, contains up-to-date data on scooters, allows easy search by country, city, and provider.

#### Suggested changes
- Update each provider separately and keep the status from the last successful update. 
  
  Currently, the application overwrites the whole data set. A better option would be to update only one provider and after a successful update, set new timestamp. The timestamp of the last successful update should be displayed on the page next to each provider, and a longer period of time without updates should be marked accordingly (e.g. with a red badge).
- Use a bundler (Vite, Webpack) to generate the frontend, split into smaller components, and perhaps use a lighter framework than Vue (Svelte?).
- Improve the speed and RWD of the frontend, add a search engine.

  Currently, the site on older hardware crashed the entire browser when loading the map. It would be necessary to optimize the loading of data in some way.
- When opening the site on a smartphone, make it possible to create a shortcut to the website on the desktop with an icon that behaves like an application (like OLX for example)
- Create an automatic GitHub action that will build the site on a separate branch.

  Currently, in order to update the page, you need to build it yourself and push the changes to the repository. 
A better solution would be to create an automatic, regular action that will build the page, preferably to a separate branch, so that only the source files remain in the main one.
- Automatic loading of providers in index.php 
  
  Currently, each provider has to be added manually to the array; this should be updated somehow (maybe by using reflection?).
- The site, for the convenience of the user, should include links to mobile apps of each provider
