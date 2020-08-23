<?php
// Defining Schema Generation Functionality
class SchemaGeneration {
    /**
     * @var $schema
     */
    protected $schema = [];
    /**
     * @var $schemaData
     */
    protected $defaultSchemaData = [
        '@context' => 'http://schema.org',
        '@type' => null,
    ];
    public function __construct(string $type)
    {
        $this->defaultSchemaData['@type'] = $type;
    }
    /**
     * Open Schema script
     * @return void
     */
    private function openSchema()
    {
        echo '<script type="application/ld+json">'.PHP_EOL;
    }
    /**
     * Close Schema script
     * @return void
     */
    private function closeSchema()
    {
        echo PHP_EOL.'</script>'.PHP_EOL;
    }
    public function setData(array $data)
    {
        $this->schema = array_merge($this->defaultSchemaData, $data);
    }
    public function print()
    {
        $this->openSchema();
        echo json_encode($this->schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->closeSchema();
    }
}

function generate_structured_data($type, $data) {
    $schema = new SchemaGeneration($type);
    $schema->setData($data);
    $schema->print();
}

// FAQ Schema
function faq_schema() {
  $all_queastions = get_post_meta( get_the_ID(), 'structured-data-faq-schema', true );
  if($all_queastions) {
    $quetion_to_json = function($question) {
      return '{ "@type": "Question",
                "name": "' . $question['structured-data-question'] . '",
                "acceptedAnswer": {
                "@type": "Answer",
                "text": "' . $question['structured-data-answer'] . '"
                }
            }';
    };
  
    $all_faqs_array = array_map($quetion_to_json, $all_queastions);
  
    $all_faqs = join(',', $all_faqs_array);
  
    echo '<script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "FAQPage",
          "mainEntity": [' . $all_faqs . ']
        }
    </script>';
  }
}
add_action('wp_head', 'faq_schema', 10);


function local_business_structured_data() {
  if(get_post_meta( get_the_ID(), 'structured-data-enable-location-schema', true ) == 1) {
    $location_schema = get_post_meta( get_the_ID(), 'location-schema', true );
    $current_location_schema = get_option('structured_data_markup')['multiple-location-schema'];
    $get_schema = array_search($location_schema, array_column($current_location_schema, 'location-title'));
    // Schema variables
    if($current_location_schema[$get_schema]['location-url']) {
      $location_url = $current_location_schema[$get_schema]['location-url'];
    } else {
      $location_url = get_bloginfo('url');
    }
    $schema_telephone = $current_location_schema[$get_schema]['phone'];
    $streetAddress = $current_location_schema[$get_schema]['street'];
    $addressLocality = $current_location_schema[$get_schema]['city'];
    $postalCode = $current_location_schema[$get_schema]['zip-code'];
    $addressRegion = $current_location_schema[$get_schema]['state'];
    $latitude = $current_location_schema[$get_schema]['latitude'];
    $longitude = $current_location_schema[$get_schema]['longitude'];
  } else {
    $location_url = get_bloginfo('url');
    $schema_telephone = get_option('structured_data_markup')['local-business-tabs']['local-business-phone'];
    $streetAddress = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-street'];
    $addressLocality = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-city'];
    $postalCode = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-zip'];
    $addressRegion = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-state'];
    $latitude = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-lat'];
    $longitude = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-long'];
  }
  $data = [
    '@type' => get_option('structured_data_markup')['local-business-tabs']['local-business-type'],
    '@id' => get_bloginfo('url') . '#LocalBusiness',
    'name' => get_option('structured_data_markup')['local-business-tabs']['local-business-name'],
    'image' => esc_url(get_option('structured_data_markup')['local-business-tabs']['local-business-image']['url']),
    'logo' => esc_url(get_option('structured_data_markup')['local-business-tabs']['local-business-image']['url']),
    'url' => $location_url,
    'telephone' => $schema_telephone,
    'priceRange' => get_option('structured_data_markup')['local-business-tabs']['local-business-price-range'],
    'address' => array(
      '@type' => 'PostalAddress',
      'streetAddress' => $streetAddress,
      'addressLocality' => $addressLocality,
      'postalCode' => $postalCode,
      'addressCountry' => get_option('structured_data_markup')['local-business-address-fieldset']['local-business-country']
    ),
    'geo' => array(
      '@type' => 'GeoCoordinates',
      'latitude' => $latitude,
      'longitude' => $longitude
    ),
  ];
  if( get_option('structured_data_markup')['local-business-address-fieldset']['local-business-country'] == 'US' ) {
    $data['address']['addressRegion'] = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-state'];
  }
  elseif ( get_option('structured_data_markup')['local-business-address-fieldset']['local-business-country'] == 'CA' ) {
    $data['address']['addressRegion'] = get_option('structured_data_markup')['local-business-address-fieldset']['local-business-region'];
  }
  $local_business_open_247 = get_option('structured_data_markup')['local-business-opening-hours-fieldset']["local-business-open-247"];
  $local_business_opening_hours = get_option('structured_data_markup')['local-business-opening-hours-fieldset']["local-business-opening-hours"];
  if ($local_business_open_247):
    $data['openingHoursSpecification']['@type'] = 'OpeningHoursSpecification';
    $data['openingHoursSpecification']['dayOfWeek'] = array(
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday'
    );
    $data['openingHoursSpecification']['opens'] = '00:00';
    $data['openingHoursSpecification']['closes'] = '23:59';
  else:
    $wh_group = [];
    foreach ( $local_business_opening_hours as $opening_hours_entry ){
      $wh_group[] = array(
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => $opening_hours_entry['local-business-opening-hours-days'],
        'opens' => $opening_hours_entry['local-business-opening-hours-opens'],
        'closes' => $opening_hours_entry['local-business-opening-hours-closes'],
      );
    };
    $data['openingHoursSpecification'] = $wh_group;
  endif;
  //Social Profiles
  if (get_option('structured_data_markup')['local-business-tabs']['use-local-business-social-profiles'] == true):
    $social_profiles = get_option('structured_data_markup')['local-business-tabs']['local-business-social-profiles'];
    $social_profiles_urls = [];
    foreach ( $social_profiles as $social_profile_link ){
      $social_profiles_urls[] = $social_profile_link["social-profile-url"];
    };
    if( $social_profiles_urls ) {
      $data['sameAs'] = $social_profiles_urls;
    }
  endif;
  generate_structured_data('Local', $data);
}
if (get_option('structured_data_markup')['use-local-business-schema'] == true) {
  add_action('wp_head', 'local_business_structured_data', 10);
}

