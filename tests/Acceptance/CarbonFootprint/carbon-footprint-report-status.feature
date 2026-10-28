Feature:
    I want the results page to show the report status

    Scenario: The report texts are being generated
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generating_texts" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Se están generando los textos del informe, te notificaremos cuando estén listos."

    Scenario: The report is pending review
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given company carbon footprint "999" is completed
        Given company carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "El informe está pendiente de revisión por el equipo de la plataforma."

    Scenario: The report was rejected and the reopened property footprint shows the comment
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 1 answered exists
        Given electricity marketer "Comercializadora" with id "EM1" and emission factor for year "2025" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-2"
        When I submit the scope "scope_2" step form excluding source "building_electricity" with reason "no_data_available" and comment "Sin datos"
        Then I am redirected to route "property_carbon_footprints_scope_3"
        When I submit the scope 3 step form excluding the whole scope with reason "no_data_available"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        When I submit the mitigation plan step form
        Then property carbon footprint "999" should be completed
        Given property carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        When I submit a form with "comment:Faltan datos de electricidad" and button "Rechazar"
        Then the report of property carbon footprint "999" should be "rejected"
        Then property carbon footprint "999" should not be completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "El informe ha sido rechazado: Faltan datos de electricidad"
        When I go to "/es/property-carbon-footprints/999/edit"
        Then I am redirected to route "property_carbon_footprints_mitigation_plan"
        Then I see a text "El informe ha sido rechazado: Faltan datos de electricidad"

    Scenario: The report was rejected and the reopened event footprint shows the comment in the wizard
        Given account "MyCompany" with id "666" exists
        Given user "admin" with role "ROLE_ADMIN" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" is completed
        Given event carbon footprint "999" has a report "pending_review" requested by "owner@dediez.software"
        When I am logged as "admin"
        When I go to "/es/admin/carbon-footprint-reports/999/review"
        When I submit a form with "comment:Revisa los materiales" and button "Rechazar"
        Then the report of event carbon footprint "999" should be "rejected"
        Then event carbon footprint "999" should not be completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/materials"
        Then the response status should be 200
        Then I see a text "El informe ha sido rechazado: Revisa los materiales"

    Scenario: The report is generated and can be downloaded from the list
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Descargar informe"
        When I go to "/es/property-carbon-footprints"
        Then the response status should be 200
        Then I see a text "/es/carbon-footprint-reports/999/download"

    Scenario: The report PDF is being generated
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "generating_pdf" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Se está generando el informe, te notificaremos cuando esté disponible."

    Scenario: A draft of an account that can no longer edit texts still shows the draft card
        Given account "MyCompany" with id "666" exists
        Given account "666" has report permissions can edit texts "0" and auto review "1"
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given company carbon footprint "999" is completed
        Given company carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Los textos del informe están listos para revisar."
        Then I do not see a text "Revisar textos del informe"

    Scenario: The generated event report can be downloaded from the events list
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" is completed
        Given event carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/events"
        Then the response status should be 200
        Then I see a text "/es/carbon-footprint-reports/999/download"

    Scenario: The generated company report can be downloaded from the companies list
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with providers answered exists
        Given company carbon footprint "999" is completed
        Given company carbon footprint "999" has a report "generated" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints"
        Then the response status should be 200
        Then I see a text "/es/carbon-footprint-reports/999/download"

    Scenario: The owner without edit texts permission does not see the review button on a draft
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with providers answered exists
        Given property carbon footprint "999" is completed
        Given property carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a text "Los textos del informe están listos para revisar."
        Then I do not see a text "Revisar textos del informe"

    Scenario: The owner without edit texts permission does not see the review button on an event draft
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given event carbon footprint "999" is completed
        Given event carbon footprint "999" has a report "draft" requested by "owner@dediez.software"
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a text "Los textos del informe están listos para revisar."
        Then I do not see a text "Revisar textos del informe"
