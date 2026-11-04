Feature:
    I want to declare the activity data of my property carbon footprints and follow their indicators in a dashboard

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel Los Olivos" with id "1111" of account "666" exists

    Scenario: Declaring the activity data from the results step calculates the intensity indicators
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Datos de actividad del año"
        When I submit a form with "stays:41000|builtAreaM2:2000" and button "Guardar datos de actividad"
        Then I am redirected to route "property_carbon_footprints_results"
        Then I see a text "Datos de actividad guardados."
        Then I see a text "kg CO₂eq / estancia"
        Then I see a text "kg CO₂eq / m²"
        Then I see a form field "intensity_denominators[stays]" with value "41000"

    Scenario: The certificate form is prefilled with the declared stays
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        Given "property" carbon footprint "1111-2025" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/1111-2025/step/results"
        Then the response status should be 200
        Then I see a text "Emitir certificado"
        Then I see a form field "issue_property_carbon_footprint_certificate[stays]" with value "41000"

    Scenario: The indicators menu redirects to the only property with completed footprints
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints"
        Then I see a link to "/es/property-carbon-footprints/kpis"
        When I go to "/es/property-carbon-footprints/kpis"
        Then I am redirected to "/es/property-carbon-footprints/kpis/1111"

    Scenario: The indicators menu shows a selector when there are several properties with completed footprints
        Given property "Hotel Mar" with id "2222" of account "666" exists
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        Given a completed property carbon footprint of property "Hotel Mar" for year "2024" with total "1000" kg and stays "0"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis"
        Then the response status should be 200
        Then I see a text "Hotel Los Olivos (2025)"
        Then I see a text "Hotel Mar (2024)"
        Then I see a button with name "Ver indicadores"

    Scenario: The dashboard shows the inventory and only the indicators with data
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/1111"
        Then the response status should be 200
        Then I see a text "Resultados del inventario"
        Then I see a text "184,50"
        Then I see a text "Alcance 1"
        Then I see a text "Indicadores de intensidad"
        Then I see a text "4,50"
        Then I see a text "kg CO₂eq / estancia"
        Then I do not see a text "kg CO₂eq / cubierto"

    Scenario: Two completed years show the evolution and the variation decomposition
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2024" with total "100000" kg and stays "40000"
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "96000" kg and stays "44000"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/1111"
        Then the response status should be 200
        Then I see a text "Evolución"
        Then I see a text "Descomposición de la variación"
        Then I see a text "vs. 2024"
        Then I see a text "Variación vs. 2024"

    Scenario: Declaring the base year marks it in the dashboard
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/1111"
        Then the response status should be 200
        When I submit a form with "year:2025|justification:Primer año con inventario completo|recalculationThresholdPercent:5" and button "Declarar año base"
        Then I am redirected to route "property_carbon_footprint_kpis"
        Then I see a text "Año base declarado."
        Then I see a text "Primer año con inventario completo"
        Then I see a button with name "Actualizar declaración"
        Then I see a link with name "Eliminar declaración"

    Scenario: The base year cannot be declared without justification
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/1111"
        Then the response status should be 200
        When I submit a form with "year:2025|justification:|recalculationThresholdPercent:5" and button "Declarar año base"
        Then I am redirected to route "property_carbon_footprint_kpis"
        Then I see a text "Este valor no debería estar vacío."
        Then I do not see a text "Año base declarado."

    Scenario: Removing the base year declaration
        Given a completed property carbon footprint of property "Hotel Los Olivos" for year "2025" with total "184500" kg and stays "41000"
        Given a base year declaration for property "Hotel Los Olivos" in "2025"
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint-kpis/base-year/base-year-1111/remove" with csrf token "remove"
        Then I am redirected to route "property_carbon_footprint_kpis"
        Then I see a text "Declaración de año base eliminada."
        Then I see a button with name "Declarar año base"

    Scenario: The dashboard of a property of another account is denied
        Given account "Other" with id "777" exists
        Given property "Hotel Ajeno" with id "3333" of account "777" exists
        Given a completed property carbon footprint of property "Hotel Ajeno" for year "2025" with total "1000" kg and stays "100"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/3333"
        Then the response status should be 403

    Scenario: A property without completed footprints shows an empty dashboard
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/1111"
        Then the response status should be 200
        Then I see a text "Todavía no hay huellas completadas de esta propiedad. Completa una huella para ver sus indicadores."

    Scenario: The owner of an account without organizational carbon footprint can't see the indicators
        Given account "666" has feature "organizational_carbon_footprint" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/kpis/1111"
        Then the response status should be 403
