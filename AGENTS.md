# AGENTS.md

## Purpose

This directory contains the WPrint 3D port of `OctoPrint-NavbarTemp`.

## Shape

- Runtime: `php`
- UI mode: `custom_bundle` settings tab + declarative navbar widget
- Footprint: `lightweight`

## Important files

- `plugin.json`: manifest, settings defaults, navbar widget, and settings surface
- `actions/snapshot.php`: OctoPrint-style snapshot action that also publishes plugin state
- `ui/settings.html`: browser-side compatibility example using `/api/plugins/sdk/octoprint-compat.js`
- `README.md`: quick overview of the port

## Porting rules

- Keep the settings keys aligned with the original OctoPrint plugin where practical.
- Prefer host-rendered navbar widgets over iframe-based navbar UI.
- Use `send_plugin_message` or `publish_state` effects when an OctoPrint port previously used `_plugin_manager.send_plugin_message(...)`.
- Prefer live preview on form input over explicit preview-refresh buttons when the host can safely render a draft snapshot without persisting it.
- If a port needs Laravel models or config, bootstrap the app from `WPRINT3D_BOOTSTRAP_APP`.

## Verification

- Install it from `Settings -> Plugins -> Add a plugin -> Install unpacked`
- Package it with `./plugin.sh pack examples/plugins/octoprint-navbartemp-port`
- Use the host-visible archive at `examples/plugins/octoprint-navbartemp-port/builds/octoprint-navbartemp-port.w3dp`
- Install the packaged `.w3dp` from `Settings -> Plugins -> Add a plugin -> Upload from file`
- Open the dedicated `NavbarTemp` plugin settings tab
- Confirm the settings-page preview updates live while editing
- Confirm only saved changes propagate to the persistent navbar state
- For the packaged install, confirm the installed card shows `Unsigned` and does not show `Live source`
