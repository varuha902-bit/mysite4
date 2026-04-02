/**
 * FaeCursor Effects Configuration
 */

const FAE_CURSOR_EFFECTS_CONFIG = {
  'none': {
    displayName: 'None',
    supportsColor: false,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: false,
    supportsHideCursor: false,
    effectType: 'none'
  },
  'drop-effect': {
    displayName: 'Drop Effect',
    supportsColor: true,
    supportsSize: true,
    supportsIcon: true,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'icon',
    animationDirection: 'down' // Icons drop down
  },
  'rise-effect': {
    displayName: 'Rise Effect',
    supportsColor: true,
    supportsSize: true,
    supportsIcon: true,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'icon',
    animationDirection: 'up' // Icons rise up
  },
  'line-effect': {
    displayName: 'Line Effect',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'line'
  },
  'duo-circle': {
    displayName: 'Duo Circle',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'circle',
    circleStyle: 'border' // Uses border style
  },
  'duo-circle-2': {
    displayName: 'Duo Circle 2',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'circle',
    circleStyle: 'fill' // Uses fill style
  },
  'bubbles-effect': {
    displayName: 'Bubbles',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'custom'
  },
  'fireworks-effect': {
    displayName: 'Fireworks',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: false,
    supportsHideCursor: true,
    effectType: 'custom'
  },
  'spark-effect': {
    displayName: 'Spark',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'custom'
  },
  'flag-effect': {
    displayName: 'Flag Cursor',
    supportsColor: true,
    supportsSize: true,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true, // Always hides cursor (cursor replacement)
    effectType: 'custom'
  },
  'genuine-effect': {
    displayName: 'Genuine',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true, // Always hides cursor (cursor replacement)
    effectType: 'custom'
  },
  'gradient-trail': {
    displayName: 'Gradient Trail',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'custom'
  },
  'leaf-effect': {
    displayName: 'Leaf Effect',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'custom'
  },
  'magic-trail': {
    displayName: 'Magic Trail',
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'custom'
  }
};

// Helper function to get effect config
function getEffectConfig(effectName) {
  return FAE_CURSOR_EFFECTS_CONFIG[effectName] || {
    displayName: effectName,
    supportsColor: true,
    supportsSize: false,
    supportsIcon: false,
    supportsSpeed: true,
    supportsHideCursor: true,
    effectType: 'custom'
  };
}

// Helper function to check if effect supports a setting
function effectSupports(effectName, setting) {
  const config = getEffectConfig(effectName);
  switch(setting) {
    case 'color': return config.supportsColor;
    case 'size': return config.supportsSize;
    case 'icon': return config.supportsIcon;
    case 'speed': return config.supportsSpeed;
    case 'hideCursor': return config.supportsHideCursor !== false; // Default to true if not specified
    default: return false;
  }
}

