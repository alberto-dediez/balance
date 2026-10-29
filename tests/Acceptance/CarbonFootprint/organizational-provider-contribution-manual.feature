Feature:
    I want to record the emissions a provider has reported to me

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given provider "Logística Sur" with id "444" of account "666" exists
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists

    Scenario: Saving reported emissions adds provider activities to the footprint
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        Then the response status should be 200
        When I save the provider contribution form with reported emissions "Reparto 2025:3200:kg,Última milla:0.8:t" in source "upstream_transport"
        Then I am redirected to route "property_carbon_footprints_providers_edit"
        Then property carbon footprint "999" should have 2 activities of provider "444" in source "upstream_transport"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        Then I see a text "Datos de proveedores"
        Then I see a text "4.000,00"
        When I go to "/es/property-carbon-footprints/999/step/providers"
        Then I see a text "4.000,00"

    Scenario: Excluding the category removes the provider activities
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with reported emissions "Reparto 2025:3200:kg" in source "upstream_transport"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"
        When I go to "/es/property-carbon-footprints/999/step/scope-3"
        When I submit the scope "scope_3" step form excluding source "upstream_transport" with reason "not_applicable" and comment "Sin transporte"
        Then property carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport"

    Scenario: Removing a category removes its provider activities
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with reported emissions "Reparto 2025:3200:kg" in source "upstream_transport"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I untick the checkbox "change_organizational_provider_contribution_sources[sources][2]"
        When I tick the checkbox "change_organizational_provider_contribution_sources[sources][3]"
        When I submit a form with "" and button "change_organizational_provider_contribution_sources_save"
        Then I am redirected to route "property_carbon_footprints_providers_edit"
        Then property carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport"

    Scenario: An invalid row does not change the categories
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with reported emissions "Reparto 2025:3200:kg" in source "upstream_transport"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with categories "operations_waste" and reported emissions "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx:10:kg" in source "operations_waste"
        Then the response status should be 200
        Then I see a text "No se han podido guardar las actividades"
        Then organizational provider contribution "777" should cover "upstream_transport"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"

    Scenario: The typed rows are kept when the form is invalid
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with categories "none" and reported emissions "Reparto tecleado:150:kg" in source "upstream_transport"
        Then the response status should be 200
        Then I see a text "Reparto tecleado"
        Then property carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport"

    Scenario: The typed rows are kept when the activities cannot be saved
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/providers/777/edit"
        When I save the provider contribution form with reported emissions "Reparto válido:150:kg,Reparto con concepto demasiado largo xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx:10:kg" in source "upstream_transport"
        Then the response status should be 200
        Then I see a text "No se han podido guardar las actividades"
        Then I see a text "Reparto válido"
        Then I see a text "Reparto con concepto demasiado largo"
        Then property carbon footprint "999" should have 0 activities of provider "444" in source "upstream_transport"

    Scenario: A completed footprint shows the contribution read only
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with employees answered exists
        Given property carbon footprint "998" is completed
        Given organizational provider contribution with id "778" for property carbon footprint "998" and provider "444" of account "666" in mode "manual" with token "" covering "upstream_transport" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/998/providers/778/edit"
        Then the response status should be 200
        Then I do not see a button with name "change_organizational_provider_contribution_sources[save]"
