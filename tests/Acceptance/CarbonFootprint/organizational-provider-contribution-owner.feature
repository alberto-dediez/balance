Feature:
    I want to manage the provider contributions of a property carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given provider "Logística Sur" with id "444" of account "666" exists

    Scenario: The providers step lists the contributions
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/providers"
        Then the response status should be 200
        Then I see a text "Logística Sur"
        Then I see a text "Añadir proveedor"
        Then I do not see a text "No hay aportaciones de proveedores para esta huella."

    Scenario: Registering a manual contribution redirects to its edition
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/register"
        Then the response status should be 200
        When I tick the checkbox "register_organizational_provider_contribution[sources][2]"
        When I submit a form with "provider:444|mode:manual" and button "Registrar"
        Then I am redirected to route "property_carbon_footprints_providers_edit"
        Then there should be 1 active organizational provider contributions for property carbon footprint "999"

    Scenario: Registering a requested contribution redirects to the providers step and notifies the provider
        Given provider "444" has a linked user
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/register"
        When I tick the checkbox "register_organizational_provider_contribution[sources][2]"
        When I submit a form with "provider:444|mode:requested" and button "Registrar"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then there should be 1 active organizational provider contributions for property carbon footprint "999"
        Then I received an email with subject "Aporta tus datos de emisiones de 2025 para Hotel" to "provider-444@example.com"

    Scenario: The request email escapes the names written by users
        Given property "Hotel <b>Sol</b>" with id "2222" of account "666" exists
        Given property carbon footprint with id "997" for property "2222" of account "666" and year "2025" with scope 3 answered exists
        Given provider "444" has a linked user
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/997/providers/register"
        When I tick the checkbox "register_organizational_provider_contribution[sources][2]"
        When I submit a form with "provider:444|mode:requested" and button "Registrar"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then the email with subject "Aporta tus datos de emisiones de 2025 para Hotel <b>Sol</b>" contains "Hotel &lt;b&gt;Sol&lt;/b&gt;"
        Then the email with subject "Aporta tus datos de emisiones de 2025 para Hotel <b>Sol</b>" does not contain "<b>Sol</b>"

    Scenario: A provider cannot have two active contributions for the same footprint
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/register"
        When I tick the checkbox "register_organizational_provider_contribution[sources][3]"
        When I submit a form with "provider:444|mode:manual" and button "Registrar"
        Then I am redirected to route "property_carbon_footprints_providers_register"
        Then I see a text "Ya existe una aportación de este proveedor para esta huella"
        Then there should be 1 active organizational provider contributions for property carbon footprint "999"

    Scenario: A requested contribution needs a provider with a linked user
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/register"
        When I tick the checkbox "register_organizational_provider_contribution[sources][2]"
        When I submit a form with "provider:444|mode:requested" and button "Registrar"
        Then I am redirected to route "property_carbon_footprints_providers_register"
        Then I see a text "El proveedor no tiene usuario vinculado"
        Then there should be 0 active organizational provider contributions for property carbon footprint "999"

    Scenario: The owner of another account cannot register contributions
        Given account "Other" with id "555" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "555" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/register"
        Then the response status should be 403

    Scenario: A contribution cannot be reached through the route of another footprint of the same type
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with scope 3 answered exists
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/providers/777/edit"
        Then the response status should be 404

    Scenario: The owner of another account cannot edit a contribution
        Given account "Other" with id "555" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "555" exists
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        Then the response status should be 403

    Scenario: The owner of another account cannot remove a contribution
        Given account "Other" with id "555" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "555" exists
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/remove" with csrf token "remove"
        Then the response status should be 403
        Then there should be 1 active organizational provider contributions for property carbon footprint "999"

    Scenario: A user cannot see the provider contributions
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        Then the response status should be 403

    Scenario: A completed footprint does not allow adding providers
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with employees answered exists
        Given property carbon footprint "998" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/step/providers"
        Then the response status should be 200
        Then I do not see a text "Añadir proveedor"

    Scenario: A manual contribution of a completed footprint can still be consulted
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with employees answered exists
        Given organizational provider contribution with id "778" for property carbon footprint "998" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        Given property carbon footprint "998" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/step/providers"
        Then the response status should be 200
        Then I see a link to "/es/property-carbon-footprints/998/providers/778"

    Scenario: Ignoring and reactivating a requested contribution
        Given provider "444" has a linked user
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "requested" with token "tok-777" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/providers"
        When I click on the action "Descartar"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then I see a text "Se ha descartado la aportación de Logística Sur"
        Then organizational provider contribution "777" should have status "ignored"
        When I go to "/es/property-carbon-footprints/999/step/providers"
        When I click on the action "Reactivar"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then I see a text "Se ha reactivado la aportación de Logística Sur"
        Then organizational provider contribution "777" should have status "pending"

    Scenario: Showing a requested contribution
        Given provider "444" has a linked user
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "requested" with token "tok-777" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777"
        Then the response status should be 200
        Then I see a text "Datos de la empresa"

    Scenario: Removing a contribution removes its activities
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with reported emissions "Reparto 2025:3200:kg" in source "upstream_transport"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"
        When I go to "/es/property-carbon-footprints/999/providers/777/remove" with csrf token "remove"
        Then I am redirected to route "property_carbon_footprints_providers"
        Then I see a text "Se ha eliminado la aportación de Logística Sur"
        Then organizational provider contribution "777" should be removed
        Then property carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport"
