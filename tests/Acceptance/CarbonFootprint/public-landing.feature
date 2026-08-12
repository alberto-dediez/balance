Feature:
    I want to make sure that a property or an event can greet whoever scans its QR with its own
    informative page before sending them to the carbon footprint form

    Scenario: An attendee scanning the QR of an event with an active landing sees it before the form
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given active public landing for "event" "333" of account "666" with title "Bienvenido al Evento A" exists
        When I go to "/es/carbon-footprint/attendee-survey/token-abc"
        Then the response status should be 200
        Then I see a text "Bienvenido al Evento A"
        Then I see a text "Antes de empezar"
        Then I see a text "Por qué lo hacemos"
        Then I see a text "Quiero calcular mi huella"

    Scenario: A guest scanning the QR of a property with an active landing sees it before the form
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        Given active public landing for "property" "777" of account "666" with title "Bienvenido al Hotel A" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc"
        Then the response status should be 200
        Then I see a text "Bienvenido al Hotel A"
        Then I see a text "Quiero calcular mi huella"

    Scenario: Without an active landing the QR still goes straight to the form
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc"
        Then I am redirected to route "public_carbon_footprint_property_trip_trip"

    Scenario: The owner activates the landing of a property from its calculator page
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777"
        Then the response status should be 200
        Then I see a text "Página de bienvenida"
        When I submit a form with "active:1|title:Bienvenido al Hotel A|subtitle:Antes de empezar|ctaLabel:Calcular" and button "Guardar"
        Then public landing for "property" "777" should be active with title "Bienvenido al Hotel A"

    Scenario: The owner edits the blocks of an existing landing
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given active public landing for "property" "777" of account "666" with title "Bienvenido al Hotel A" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777"
        Then the response status should be 200
        Then I see a text "Por qué lo hacemos"
        When I submit a form with "active:1|title:Bienvenido al Hotel A|ctaLabel:Calcular|[blocks][0][title]:Por qué medimos|[blocks][0][text]:Porque medir importa" and button "Guardar"
        Then public landing for "property" "777" should be active with title "Bienvenido al Hotel A"
        Then public landing for "property" "777" should have 1 blocks

    Scenario: The owner configures the landing of an event from its calculator page
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/attendee-survey"
        Then the response status should be 200
        Then I see a text "Página de bienvenida"
        When I submit a form with "active:1|title:Bienvenido al Evento A|ctaLabel:Calcular" and button "Guardar"
        Then public landing for "event" "333" should be active with title "Bienvenido al Evento A"

    Scenario: An owner of another account cannot configure the landing of a property
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "667" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "667" exists
        Given property "Hotel A" with id "777" of account "666" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprint/property-trip-calculations/777"
        Then the response status should be 403
