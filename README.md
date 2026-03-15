# OctoPrint NavbarTemp Port

This example ports the behavior of `OctoPrint-NavbarTemp` into WPrint 3D.

It demonstrates:

- a native `navbar_widget` rendered through the host `data_strip` component as an inline telemetry lane across the main navbar
- a `custom_bundle` settings page that uses the host-served OctoPrint compatibility helper
- persistent plugin settings through `/api/plugins/{id}/settings`
- OctoPrint-style plugin message/state publishing through the `send_plugin_message` effect

Use it as the reference case when porting OctoPrint plugins that previously depended on:

- `SettingsPlugin`
- `TemplatePlugin`
- `AssetPlugin`
- `send_plugin_message`
- Knockout/JS view models for navbar or settings UI

Install it from the development stack through:

- `Settings -> Plugins -> Add a plugin -> Install unpacked`

## Packaging and release

Develop this plugin from a full `wprint3d-core` source checkout. The repository is small, and the supported workflow relies on that checkout for `./plugin.sh`, live-source mounts, and `.w3dp` packaging/signing.

Package it:

```bash
./plugin.sh pack examples/plugins/octoprint-navbartemp-port
```

Archive output:

```text
examples/plugins/octoprint-navbartemp-port/builds/octoprint-navbartemp-port.w3dp
```

Sign it if you plan to distribute it:

```bash
mkdir -p keys
openssl genpkey -algorithm RSA -out keys/octoprint-navbartemp-port-private.pem -pkeyopt rsa_keygen_bits:4096
./plugin.sh pack examples/plugins/octoprint-navbartemp-port --signing-key=keys/octoprint-navbartemp-port-private.pem
```

Keep the private key outside the plugin directory and out of version control.
For the full signing, verification, and registry submission flow, see `/home/facuarmo/wprint3d-core/docs/plugin-signing-for-developers.md`.

Install the packaged archive:

```bash
./plugin.sh install examples/plugins/octoprint-navbartemp-port/builds/octoprint-navbartemp-port.w3dp
./plugin.sh enable octoprint.navbartemp-port
```

Or upload it through `Settings -> Plugins -> Add a plugin -> Upload from file`.

For public-registry inclusion, keep the plugin in its own repository, open a PR against the public registry with that repository URL, and wait for the WPrint 3D team to follow up.

Then:

- install `OctoPrint NavbarTemp Port`
- enable it from the plugin inventory
- open the dedicated `NavbarTemp` settings tab
- change `Custom command label` and `Custom command`
- watch the preview update live as you tweak the form
- save settings when you want those changes persisted to the plugin and navbar state

The native WPrint 3D navbar strip and the settings-page preview should show the same published plugin state. The settings page now uses host-backed draft snapshots for instant preview updates while editing, but only saved changes are pushed into the persistent plugin settings and live navbar state. In the host shell, the navbar widget is rendered as inline text-and-icon telemetry so OctoPrint-style temperature ports sit naturally between the app title and the user/menu actions instead of looking like detached settings chips.

For the packaged `.w3dp` release path, confirm that the installed plugin card shows `Unsigned` and does not show `Live source`; that proves you are running the packaged artifact instead of the development mount.
