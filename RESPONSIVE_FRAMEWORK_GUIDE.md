# Southrift Services Responsive Framework Guide

This document provides guidelines for implementing responsive design across all pages of the Southrift Services web application using the standardized responsive framework.

## Framework Overview

The responsive framework provides a consistent, mobile-first approach to responsive design with predefined breakpoints, components, and utilities that work across all device sizes.

## File Structure

```
css/
└── responsive-framework.css  # Main responsive framework file
```

## How to Implement

### 1. Include the Framework

Add the following line to the `<head>` section of your HTML files, after any existing CSS but before your custom styles:

```html
<link rel="stylesheet" href="css/responsive-framework.css">
```

### 2. Apply Framework Classes

Use the predefined classes to create responsive layouts:

#### Container
```html
<div class="container">
  <!-- Your content here -->
</div>
```

#### Sections
```html
<div class="section">
  <h2 class="section-title">Section Title</h2>
  <!-- Your content here -->
</div>
```

#### Grid System
```html
<div class="card-grid">
  <div class="card">
    <!-- Card content -->
  </div>
  <div class="card">
    <!-- Card content -->
  </div>
  <div class="card">
    <!-- Card content -->
  </div>
</div>
```

#### Buttons
```html
<button class="btn">Primary Button</button>
<a href="#" class="btn btn-secondary">Secondary Button</a>
```

#### Forms
```html
<form>
  <div class="form-group">
    <label for="name">Name</label>
    <input type="text" id="name" class="form-control" placeholder="Enter your name">
  </div>
  <div class="form-group">
    <label for="email">Email</label>
    <input type="email" id="email" class="form-control" placeholder="Enter your email">
  </div>
  <button type="submit" class="btn">Submit</button>
</form>
```

#### Tables
```html
<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>Header 1</th>
        <th>Header 2</th>
        <th>Header 3</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Data 1</td>
        <td>Data 2</td>
        <td>Data 3</td>
      </tr>
    </tbody>
  </table>
</div>
```

## Breakpoints

The framework includes the following breakpoints:

| Device Size | Breakpoint | Class Prefix |
|-------------|------------|--------------|
| Extra Extra Small | < 360px | `xxs` |
| Extra Small | 360px - 479px | `xs` |
| Small | 480px - 575px | `sm` |
| Medium | 576px - 767px | `md` |
| Large | 768px - 991px | `lg` |
| Extra Large | 992px - 1199px | `xl` |
| Extra Extra Large | ≥ 1200px | `xxl` |

## Responsive Utilities

### Padding for Fixed Footer
The framework automatically handles padding for fixed footers:
- Desktop: 80px
- Tablet: 100px
- Mobile: 120px
- Small Mobile: 130px
- Very Small Mobile: 140px
- Extra Small Devices: 150px

This is applied to the `main` element automatically.

### Typography Scaling
Headings and text elements automatically scale based on screen size.

### Component Responsiveness
All components (cards, buttons, forms, tables) are responsive by default.

## Customization

To customize the framework for specific pages:

1. Include the framework CSS first
2. Add your custom CSS after the framework
3. Override framework styles as needed using the same breakpoints

Example:
```html
<link rel="stylesheet" href="css/responsive-framework.css">
<style>
  /* Custom styles */
  @media (max-width: 768px) {
    .custom-component {
      padding: 1rem;
    }
  }
</style>
```

## Best Practices

1. **Mobile-First Approach**: Start with mobile styles and enhance for larger screens
2. **Flexible Units**: Use relative units (%, em, rem, vw) instead of fixed units (px) where possible
3. **Touch Targets**: Ensure interactive elements are at least 44px in size
4. **Content Prioritization**: Show essential content first on mobile
5. **Performance**: Optimize images and assets for different screen sizes
6. **Testing**: Test on actual devices, not just browser dev tools

## Components Included

1. **Navigation**: Responsive navbar that collapses on mobile
2. **Grid System**: Flexible card grid that adapts to screen size
3. **Typography**: Scalable text that remains readable on all devices
4. **Forms**: Adaptable form layouts for mobile input
5. **Buttons**: Properly sized touch targets
6. **Tables**: Responsive tables with horizontal scrolling on small screens
7. **Cards**: Flexible card layouts that stack on mobile
8. **Footer**: Fixed footer with proper spacing

## Testing Guidelines

1. Test on multiple device sizes:
   - Mobile (320px, 360px, 414px)
   - Tablet (768px, 1024px)
   - Desktop (1200px, 1440px, 1920px)

2. Test orientation changes:
   - Portrait to landscape
   - Landscape to portrait

3. Test with various content lengths:
   - Short content
   - Long content
   - Content with images

4. Test interactive elements:
   - Touch targets
   - Form inputs
   - Navigation

## Maintenance

To keep the framework up-to-date:

1. Regularly review and update breakpoints as needed
2. Add new components as the application grows
3. Optimize for performance
4. Ensure cross-browser compatibility
5. Document any customizations

## Support

For issues with the responsive framework, contact the development team or refer to this documentation.