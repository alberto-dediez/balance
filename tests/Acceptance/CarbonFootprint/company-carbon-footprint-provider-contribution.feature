Feature:
    I want to collect provider data in a company carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property "1111" has company "C1" assigned
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 3 answered exists
        Given provider "Logística Sur" with id "444" of account "666" exists

    Scenario: A manual contribution assigned to a property adds its emissions to the company footprint
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/providers/register"
        Then the response status should be 200
        Then I see a text "Sin asignar a propiedad"
        When I tick the checkbox "register_organizational_provider_contribution[sources][2]"
        When I submit a form with "provider:444|mode:manual|property:1111" and button "Registrar"
        Then I am redirected to route "company_carbon_footprints_providers_edit"
        When I save the provider contribution form with reported emissions "Reparto 2025:3200:kg" in source "upstream_transport"
        Then company carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport" for property "1111"
        Then company carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport" for property "unassigned"
        Then company carbon footprint "999" should have 3200 kg in source "upstream_transport"
        When I go to "/es/company-carbon-footprints/999/step/providers"
        Then I see a text "3.200,00"

    Scenario: A manual contribution without property adds its emissions to the unassigned block
        Given organizational provider contribution with id "777" for company carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/providers/777/edit"
        Then I see a text "Sin asignar a propiedad"
        When I save the provider contribution form with reported emissions "Última milla:0.8:t" in source "upstream_transport"
        Then I am redirected to route "company_carbon_footprints_providers_edit"
        Then company carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport" for property "unassigned"
        Then company carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport" for property "1111"
        Then company carbon footprint "999" should have 800 kg in source "upstream_transport"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then I see a text "Datos de proveedores"
        Then I see a text "800,00"

    Scenario: A contribution of a company footprint cannot be reached through a property route
        Given organizational provider contribution with id "777" for company carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        Then the response status should be 404
